<?php
/**
 * 2019 Payson AB
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 *  @author    Payson AB <integration@payson.se>
 *  @copyright 2019 Payson AB
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

namespace Payson\Payments\Implementation;

use Payson\Payments\Transport\Connector;
use Payson\Payments\Validation\ValidateAccountData;
use Payson\Payments\Validation\ValidateCreateCheckoutData;
use Payson\Payments\Validation\ValidateGetCheckoutData;
use Payson\Payments\Validation\ValidateUpdateCheckoutData;
use Payson\Payments\Validation\ValidateListCheckoutsData;
use Payson\Payments\Validation\ValidateCreateRecurringSubscriptionData;
use Payson\Payments\Validation\ValidateGetRecurringSubscriptionData;
use Payson\Payments\Validation\ValidateUpdateRecurringSubscriptionData;
use Payson\Payments\Validation\ValidateListRecurringSubscriptionsData;
use Payson\Payments\Validation\ValidateCreateRecurringPaymentData;
use Payson\Payments\Validation\ValidateGetRecurringPaymentData;
use Payson\Payments\Validation\ValidateUpdateRecurringPaymentData;
use Payson\Payments\Validation\ValidateListRecurringPaymentsData;

class ImplementationFactory
{
    /* PaysonAccount info*/
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnGetAccountInfoClass(Connector $connector)
    {
        return new GetAccountInfo($connector, new ValidateAccountData());
    }
    
    /* Payson Checkout 2.0 */
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnCreateCheckoutClass(Connector $connector)
    {
        return new CreateCheckout($connector, new ValidateCreateCheckoutData());
    }

    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnGetCheckoutClass(Connector $connector)
    {
        return new GetCheckout($connector, new ValidateGetCheckoutData());
    }

    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnUpdateCheckoutClass(Connector $connector)
    {
        return new UpdateCheckout($connector, new ValidateUpdateCheckoutData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnListCheckoutsClass(Connector $connector)
    {
        return new ListCheckouts($connector, new ValidateListCheckoutsData());
    }
    
    /* Recurring Subscription */
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnCreateRecurringSubscriptionClass(Connector $connector)
    {
        return new CreateRecurringSubscription($connector, new ValidateCreateRecurringSubscriptionData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnGetRecurringSubscriptionClass(Connector $connector)
    {
        return new GetRecurringSubscription($connector, new ValidateGetRecurringSubscriptionData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnUpdateRecurringSubscriptionClass(Connector $connector)
    {
        return new UpdateRecurringSubscription($connector, new ValidateGetRecurringSubscriptionData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnListRecurringSubscriptionsClass(Connector $connector)
    {
        return new ListRecurringSubscriptions($connector, new ValidateListRecurringSubscriptionsData());
    }
    
    /* Recurring Payment */
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnCreateRecurringPaymentClass(Connector $connector)
    {
        return new CreateRecurringPayment($connector, new ValidateCreateRecurringPaymentData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnGetRecurringPaymentClass(Connector $connector)
    {
        return new GetRecurringPayment($connector, new ValidateGetRecurringPaymentData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnUpdateRecurringPaymentClass(Connector $connector)
    {
        return new UpdateRecurringPayment($connector, new ValidateGetRecurringPaymentData());
    }
    
    /**
     * @param Connector $connector
     * @return ImplementationInterface
     */
    public static function returnListRecurringPaymentsClass(Connector $connector)
    {
        return new ListRecurringPayments($connector, new ValidateListRecurringPaymentsData());
    }
}
