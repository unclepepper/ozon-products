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

namespace BaksDev\Ozon\Products\Api\Card\Stocks\Update;

use App\Kernel;
use BaksDev\Ozon\Api\Ozon;
use DomainException;
use Generator;

/**
 *  Обновить количество товаров на складах
 * @see https://docs.ozon.ru/api/seller/#operation/ProductAPI_ImportProductsStocks
 */
final class OzonStockUpdateRequest extends Ozon
{
    private string $article;

    private int $total = 0;

    private int $warehouse;

    private int|false $product = false;

    public function article(string $article): self
    {
        $this->article = $article;
        return $this;
    }

    public function total(int $total): self
    {
        $this->total = $total;
        return $this;
    }

    public function warehouse(int $warehouse): self
    {

        $this->warehouse = $warehouse;
        return $this;
    }

    public function product(?int $product): self
    {
        if (null === $product)
        {
            $this->product = false;
        }
        else
        {
            $this->product = $product;
        }

        return $this;
    }


    public function update(): Generator
    {

        /**
         * Выполнять операции запроса ТОЛЬКО в PROD окружении
         */
        if($this->isExecuteEnvironment() === false)
        {
            return true;
        }


        $stocks["offer_id"]         = $this->article;

        $stocks["stock"]            = $this->total;

        $stocks["warehouse_id"]     = $this->warehouse;

        if ($this->product)
        {
            $stocks["product_id"]   = $this->product;
        }


        /**
         *
         * Пример запроса:
         * "stocks": [
         *      {
         *          "offer_id": "PG-2404С1",
         *          "product_id": 55946,
         *          "stock": 4,
         *          "warehouse_id": 22142605386000
         *      }
         *  ]
         *
         */
        $response = $this->TokenHttpClient()
            ->request(
                'POST',
                '/v2/products/stocks',
                [
                    "json" => [
                        'stocks' => [$stocks]
                    ]
                ]
            );

        $content = $response->toArray(false);

        if ($response->getStatusCode() !== 200)
        {

            $this->logger->critical($content['code'] . ': ' . $content['message'], [self::class . ':' . __LINE__]);


            throw new DomainException(
                message: 'Ошибка ' . self::class,
                code: $response->getStatusCode()
            );
        }

        foreach ($content['result'] as $item)
        {
            yield new OzonStockUpdateDTO($item);
        }
    }
}
