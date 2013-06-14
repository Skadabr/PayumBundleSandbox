<?php
namespace Acme\PayexBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Range;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;

use Payum\Registry\RegistryInterface;
use Payum\Payex\Api\OrderApi;
use Payum\Payex\Model\PaymentDetails;
use Payum\Bundle\PayumBundle\Service\TokenManager;

class PurchaseExamplesController extends Controller
{
    /**
     * @Extra\Route(
     *   "/prepare_simple_purchase", 
     *   name="acme_payex_prepare_simple_purchase"
     * )
     * 
     * @Extra\Template
     */
    public function prepareAction(Request $request)
    {
        $paymentName = 'payex';
        
        $form = $this->createPurchaseForm();
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                $data = $form->getData();
                
                $storage = $this->getPayum()->getStorageForClass(
                    'Acme\PayexBundle\Model\PaymentDetails',
                    $paymentName
                );

//                'price' => 1000,
//            'priceArgList' => '',
//            'vat' => 0,
//            'currency' => 'NOK',
//            'orderID' => 123,
//            'productNumber' => 123,
//            'purchaseOperation' => OrderApi::PURCHASEOPERATION_AUTHORIZATION,
//            'view' => OrderApi::VIEW_CREDITCARD,
//            'description' => 'a description',
//            'additionalValues' => '',
//            'returnUrl' => 'http://example.com/a_return_url',
//            'cancelUrl' => 'http://example.com/a_cancel_url',
//            'externalID' => '',
//            'clientIPAddress' => '127.0.0.1',
//            'clientIdentifier' => 'USER-AGENT=cli-php',
//            'agreementRef' => '',
//            'clientLanguage' => 'en-US',

                /** @var $paymentDetails PaymentDetails */
                $paymentDetails = $storage->createModel();
                $paymentDetails->setPrice($data['amount'] * 100);
                $paymentDetails->setPriceArgList('');
                $paymentDetails->setVat(0);
                $paymentDetails->setCurrency($data['currency']);
                $paymentDetails->setOrderId(123);
                $paymentDetails->setProductNumber(123);
                $paymentDetails->setPurchaseOperation(OrderApi::PURCHASEOPERATION_AUTHORIZATION);
                $paymentDetails->setView(OrderApi::VIEW_CREDITCARD);
                $paymentDetails->setDescription('a desc');
                $paymentDetails->setClientIPAddress($request->getClientIp());
                $paymentDetails->setClientIdentifier('');
                $paymentDetails->setAdditionalValues('');
                $paymentDetails->setAgreementRef('');
                $paymentDetails->setClientLanguage('en-US');
                
                $storage->updateModel($paymentDetails);
                
                $captureToken = $this->getTokenManager()->createTokenForCaptureRoute(
                    $paymentName,
                    $paymentDetails,
                    'acme_payment_details_view'
                );
                
                $paymentDetails->setReturnurl($captureToken->getTargetUrl());
                $paymentDetails->setCancelurl($captureToken->getTargetUrl());
                $storage->updateModel($paymentDetails);

                return $this->redirect($captureToken->getTargetUrl());
            }
        }
        
        return array(
            'form' => $form->createView()
        );
    }

    /**
     * @return \Symfony\Component\Form\Form
     */
    protected function createPurchaseForm()
    {
        return $this->createFormBuilder()
            ->add('amount', null, array(
                'data' => 1,
                'constraints' => array(new Range(array('max' => 2)))
            ))
            ->add('currency', null, array('data' => 'USD'))
            ->getForm()
        ;
    }

    /**
     * @return RegistryInterface
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }

    /**
     * @return TokenManager
     */
    protected function getTokenManager()
    {
        return $this->get('payum.token_manager');
    }
}