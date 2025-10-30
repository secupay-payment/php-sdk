<?php
/**
 * Secupay AG Php SDK
 *
 * This library allows to interact with the Secupay AG payment service.
 *
 * Copyright owner: Wallee AG
 * Website: https://secupay.com/en
 * Developer email: ecosystem-team@wallee.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Secupay\Sdk\Test;

use Secupay\Sdk\Model\AddressCreate;
use Secupay\Sdk\Model\LineItemCreate;
use Secupay\Sdk\Model\LineItemType;
use Secupay\Sdk\Model\TransactionCreate;

/**
 * Utility class for test helpers.
 */
class TestUtils
{
    /**
     * Prevent instantiation.
     */
    private function __construct()
    {
        throw new \LogicException("Utility class should not be instantiated");
    }

    /**
     * Returns a TransactionCreate payload with line item and address set.
     *
     * @return TransactionCreate
     */
    public static function getTransactionCreatePayload(): TransactionCreate
    {
        $lineItem = (new LineItemCreate())
            ->setName("Red T-Shirt")
            ->setUniqueId("5412")
            ->setType(LineItemType::PRODUCT)
            ->setQuantity(1)
            ->setAmountIncludingTax(29.95)
            ->setSku("red-t-shirt-789");

        $email = "test@domain.com";

        $address = (new AddressCreate())
            ->setCity("Winterthur")
            ->setCountry("CH")
            ->setEmailAddress($email)
            ->setFamilyName("Customer")
            ->setGivenName("Good")
            ->setPostcode("8400")
            ->setPostalState("ZH")
            ->setOrganizationName("Test GmbH")
            ->setMobilePhoneNumber("+41791234567")
            ->setSalutation("Ms");

        $transaction = (new TransactionCreate())
            ->setAutoConfirmationEnabled(true)
            ->setCurrency("CHF")
            ->setLanguage("en-US")
            ->setCustomerId("1234")
            ->setCustomerEmailAddress($email)
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setLineItems([$lineItem]);

        return $transaction;
    }
}
