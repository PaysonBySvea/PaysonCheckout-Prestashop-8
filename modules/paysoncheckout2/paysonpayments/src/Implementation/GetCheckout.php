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

use Payson\Payments\Model\Request;
use Payson\Payments\Exception\PaysonException;

class GetCheckout extends ImplementationManager
{
    protected $apiUrl = '/Checkouts/';

    /**
     * Request body - JSON
     *
     * @var Request $requestModel
     */
    private $requestModel;

    /**
     * @param array $data
     * @throws PaysonException
     */
    public function validateData($data)
    {
        $validator = $this->validator;
        $validator->validate($data);
    }

    /**
     * Prepare body data for API call
     *
     * @param array $data
     */
    public function prepareData($data)
    {
        $checkoutId = $data['id'];
        $this->requestModel = new Request();
        $this->requestModel->setGetMethod();
        $this->requestModel->setApiUrl($this->connector->getBaseApiUrl() . $this->apiUrl . $checkoutId);
    }

    /**
     * Invoke request call
     *
     * @throws PaysonException
     */
    public function invoke()
    {
        $this->responseHandler = $this->connector->sendRequest($this->requestModel);
    }

    /**
     * @return Request
     */
    public function getRequestModel()
    {
        return $this->requestModel;
    }

    /**
     * @param Request $requestModel
     */
    public function setRequestModel($requestModel)
    {
        $this->requestModel = $requestModel;
    }
}
