<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Ozon\Products\Api\Card\Update;

use App\Kernel;
use BaksDev\Ozon\Api\Ozon;
use BaksDev\Reference\Money\Type\Money;

final class UpdateOzonCardRequest extends Ozon
{
    /**
     * Создать или обновить товар
     * @see https://docs.ozon.ru/api/seller/#operation/DescriptionCategoryAPI_SearchAttributeValues
     */
    public function update(array $card): int|false
    {
        /**
         * Выполнять операции запроса ТОЛЬКО в PROD окружении
         */
        if($this->isExecuteEnvironment() === false)
        {
            return false;
        }

        /**
         * Добавляем к стоимости товара с услугами торговую надбавку
         */
        if(!empty($this->getPercent()))
        {
            $price = new Money($card['price']);
            $percent = $price->percent($this->getPercent());
            $price->add($percent);
            $card['price'] = (string) $price->getRoundValue(10);
        }

        $response = $this->TokenHttpClient()
            ->request(
                'POST',
                '/v3/product/import',
                [
                    "json" => ['items' => [$card]]
                ]
            );

        $content = $response->toArray(false);

        if($response->getStatusCode() !== 200)
        {
            $this->logger->critical($content['code'].': '.$content['message'], [self::class.':'.__LINE__]);

            return false;
        }

        if(false === isset($content['result']))
        {
            return false;
        }

        return (int) current($content['result']);
    }
}
