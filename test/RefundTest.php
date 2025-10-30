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

use PHPUnit\Framework\TestCase;
use Secupay\Sdk\ApiException;
use Secupay\Sdk\Model\RefundCreate;
use Secupay\Sdk\Model\RefundState;
use Secupay\Sdk\Model\RefundType;
use Secupay\Sdk\Model\Transaction;
use Secupay\Sdk\Model\TransactionCompletionState;
use Secupay\Sdk\Model\TransactionState;
use Secupay\Sdk\Service\RefundsService;
use Secupay\Sdk\Service\TransactionsService;
use Secupay\Sdk\Test\Constants;
use Secupay\Sdk\Test\TestUtils;

class RefundTest extends TestCase
{
    private static ?RefundsService $refundService = null;
    private static ?TransactionsService $transactionService = null;

    public static function setUpBeforeClass(): void
    {
    }

    public function testPlaceholder(): void
    {
        $this->assertTrue(true);
    }

}
