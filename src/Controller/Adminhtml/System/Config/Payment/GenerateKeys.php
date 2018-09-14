<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is released under commercial license by Lamia Oy.
 *
 * @copyright Copyright (c) 2018 Lamia Oy (https://lamia.fi)
 */


namespace Verifone\Payment\Controller\Adminhtml\System\Config\Payment;


use Verifone\Core\DependencyInjection\CryptUtils\RsaKeyGenerator;
use Verifone\Payment\Helper\Path;

class GenerateKeys extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Refresh constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_scopeConfig = $scopeConfig;

    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {

        /**
         * @var \Magento\Framework\Controller\Result\Json $resultJson
         */
        $resultJson = $this->resultJsonFactory->create();

        $request = $this->getRequest();

        $merchantAgreementCode = $request->getParam('merchant_agreement_code', null);
        $shopPrivateKeyfile = $request->getParam('shop_private_keyfile', null);

        if(
            $this->_scopeConfig->getValue(Path::XML_PATH_MERCHANT_CODE) != $merchantAgreementCode ||
            $this->_scopeConfig->getValue(Path::XML_PATH_KEY_SHOP) != $shopPrivateKeyfile
        ) {
            return $resultJson->setData(
                [
                    'valid' => false,
                    'message' => __('Please save the configuration first, and then try again. '),
                ]
            );
        }

        $generator = new RsaKeyGenerator();
        $result = $generator->generate();

        if(!$result) {
            return $resultJson->setData(
                [
                    'valid' => false,
                    'message' => __('Problem with generate new keys.'),
                ]
            );
        }

        $dir = \dirname($shopPrivateKeyfile);
        $prefix = $dir . DIRECTORY_SEPARATOR . $merchantAgreementCode;

        if($this->_scopeConfig->getValue(Path::XML_PATH_KEY_SHOP_TEST) == $shopPrivateKeyfile) {

            $msg = __('Problem with generates new keys. The path for creating the new private key is the same as for test. Please first change configuration for field <strong>%s</strong>, save, and then try again.');

            return $resultJson->setData(
                [
                    'valid' => false,
                    'message' => sprintf($msg, __('Shop private key filename'))
                ]
            );
        }

        \file_put_contents($shopPrivateKeyfile , $generator->getPrivateKey());
        \file_put_contents($prefix . '-public.pem', $generator->getPublicKey());

        return $resultJson->setData(
            [
                'valid' => true,
                'message' => __('Keys are generated correctly. Please refresh page.'),
                'public-key' => $generator->getPublicKey()
            ]
        );

    }
}