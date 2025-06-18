<?php

class msMCD
{
    /** @var modX $modx */
    public $modx;
    /** @var miniShop2 $miniShop2 */
    public $miniShop2;
    /** @var pdoTools $pdoTools */
    protected $pdoTools;

    /** @var string $ctx */
    protected $ctx;


    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = array())
    {
        $this->modx = &$modx;

        $corePath = $this->modx->getOption(
            'msmcd_core_path',
            $config,
            $this->modx->getOption('core_path') . 'components/msmcd/'
        );
        $assetsUrl = $this->modx->getOption(
            'msmcd_assets_url',
            $config,
            $this->modx->getOption('assets_url') . 'components/msmcd/'
        );
        $actionUrl = $assetsUrl . 'action.php';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'actionUrl' => $actionUrl,

            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'snippetsPath' => $corePath . 'elements/snippets/',

            'fields_mini_cart' => $this->modx->getOption(
                'msmcd_fields_mini_cart',
                null,
                'pagetitle'
            ),
        ), $config);

        $this->modx->addPackage('msmcd', $this->config['modelPath']);
        $this->modx->lexicon->load('msmcd:default');

        $this->ctx = $this->modx->context->key;
        if ($this->miniShop2 = $this->modx->getService('miniShop2')) {
            $this->miniShop2->initialize($this->ctx);
        }
        $this->pdoTools = $this->modx->getService('pdoTools');
    }

    /**
     *
     */
    public function initialize()
    {
        $this->miniShop2 = $this->modx->getService('miniShop2');
        $this->pdoTools = $this->modx->getService('pdoTools');
    }


    /**
     * Обрабатывает Ajax запросы
     * @param $action
     * @return array
     */
    public function handleRequest($action, $ctx = 'web')
    {
        $this->ctx = $ctx;

        switch ($action) {
            case 'msmcd/chunk':
                $tmp = $this->getSessionData();
                $chunk = $this->getMCDChunk($tmp['tpl'], $tmp['tplOuter'], 'ajax');
                $response = $this->success('', array('tpl' => $chunk));

                break;
            default:
                $response = $this->error('');
        }
        return $response;
    }


    /**
     * @param string $tpl
     * @param string $tplOuter
     * @param string $mode
     * @return mixed
     */
    public function getMCDChunk($tpl = '', $tplOuter = '', $mode = 'snippet')
    {
        $output = '';
        $this->miniShop2->cart->initialize($this->ctx);
        $cart = $this->miniShop2->cart->status();
        $cart['cart'] = $this->getCart();
        $cart['total_cost'] = $this->miniShop2->formatPrice($cart['total_cost']);
        $cart['total_weight'] = $this->miniShop2->formatWeight($cart['total_weight']);
        if ($mode == 'snippet') {
            $data = $this->pdoTools->getChunk($tpl, $cart);
            $output = $this->pdoTools->getChunk($tplOuter, array(
                'output' => $data,
                'total_count' => $cart['total_count'],
                'total_cost' => $cart['total_cost'],
            ));
        } elseif ($mode == 'ajax') {
            $output = $this->getChunk($tpl, $cart);
        }
        return $this->parserTag($output);
    }


    /**
     * @param $tpl
     * @param $data
     * @return mixed
     */
    public function getChunk($tpl, $data)
    {
        return $this->pdoTools->getChunk($tpl, $data);
    }


    /**
     * Обновляет корзину
     * @param $key
     */
    public function updateMiniCart($key)
    {
        $cart = $this->getCart();
        if ($cart[$key]) {
            $product = $this->getSessionProduct();
            $cart[$key]['sum'] = $this->miniShop2->formatPrice($cart[$key]['count'] * $cart[$key]['price']);
            if ($product) {
                $data = array_merge($product, $cart[$key]);
                $_SESSION['minishop2']['cart'][$key] = $data;
            }
        }
        return;
    }


    /**
     * @param $key
     * @param $count
     * @param $price
     */
    public function updateSumRow($key, $count, $price)
    {
        $_SESSION['minishop2']['cart'][$key]['sum'] = $this->miniShop2->formatPrice($count * $price);
    }


    /**
     * @param array $product
     */
    public function setSessionProduct(array $product)
    {
        $_SESSION['msMCD']['product'] = [];
        $tmp = [];
        if ($fields = array_map('trim', explode(',', $this->config['fields_mini_cart']))) {
            foreach ($fields as $field) {
                $tmp[$field] = $product[$field];
            }
            if (!empty($product['image'])) {
                $data = $this->getSessionData();
                $tmp['img'] = str_replace($product['id'] . '/small', $product['id'] . '/'
                    . $data['img'], $product['thumb']);
            }
            $_SESSION['msMCD']['product'] = $tmp;
        }
        return;
    }


    /**
     * @return array|bool
     */
    public function getCart()
    {
        if ($cart = $this->miniShop2->cart->get()) {
            return $cart;
        }
        return false;
    }


    /**
     * @param string $message
     * @param array $data
     * @return array
     */
    public function success($message = '', $data = array())
    {
        $response = array(
            'success' => true,
            'message' => $this->modx->lexicon($message),
            'data' => $data,
        );
        return $response;
    }


    /**
     * @param string $message
     * @param array $data
     * @return array
     */
    public function error($message = '', $data = array())
    {
        $response = array(
            'success' => false,
            'message' => $this->modx->lexicon($message),
            'data' => $data,
        );
        return $response;
    }


    /**
     * @param $content
     * @return mixed
     */
    private function parserTag($content)
    {
        $this->modx->getParser()
            ->processElementTags('', $content, false, false, '[[', ']]', array(), 10);
        $this->modx->getParser()
            ->processElementTags('', $content, true, true, '[[', ']]', array(), 10);
        return $content;
    }


    /**
     * @return mixed
     */
    private function getSessionData()
    {
        return $_SESSION['msMCD']['data'];
    }


    /**
     * @return mixed
     */
    private function getSessionProduct()
    {
        return $_SESSION['msMCD']['product'];
    }
}
