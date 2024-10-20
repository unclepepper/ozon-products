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

namespace BaksDev\Ozon\Products\Mapper\Property\Collection;

use BaksDev\Ozon\Products\Mapper\Attribute\Collection\Tire\SeasonOzonProductsAttribute;
use BaksDev\Ozon\Products\Mapper\Attribute\Collection\TypeOzonProductsAttribute;
use BaksDev\Ozon\Products\Mapper\Property\OzonProductsPropertyInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AutoconfigureTag('baks.ozon.product.property')]
final class NameOzonProductsProperty implements OzonProductsPropertyInterface
{
    /**
     * Название товара. До 500 символов.
     *
     * string
     * example: "name": "Комплект защитных плёнок для X3 NFC. Темный хлопок"
     */

    public const string PARAM = 'name';

    public function __construct(
        private ?TranslatorInterface $translator = null,
    ) {}

    public function getValue(): string
    {
        return self::PARAM;
    }

    /**
     * Возвращает состояние
     */
    public function getData(array $data): mixed
    {
        if(!isset($data['ozon_category']))
        {
            return false;
        }

        $name = '';

        if(isset($data['product_attributes']))
        {

            $productAttributes = json_decode(
                $data['product_attributes'],
                false,
                512,
                JSON_THROW_ON_ERROR
            );

            /** Добавляем к названию сезонность */
            $Season = new SeasonOzonProductsAttribute();


            foreach($productAttributes as $productAttribute)
            {

                if($Season::equals($productAttribute->id))
                {
                    $value = $Season::getConvertName($productAttribute->value);

                    if(!null == $value)
                    {
                        $name .= $value.' ';
                    }
                }
            }
        }

        if($this->translator)
        {
            $typeName = $this->translator->trans(
                $data['ozon_type'].'.name',
                domain: 'ozon-products.mapper'
            );

            $name .= $typeName.' ';
        }

        $name = mb_strtolower($name);
        $name = mb_ucfirst($name);


        $name .= $data['product_name'];

        if($data['product_variation_value'])
        {
            $name .= ' '.$data['product_variation_value'];
        }

        if($data['product_modification_value'])
        {
            $name .= '/'.$data['product_modification_value'];
        }

        if($data['product_offer_value'])
        {
            $name .= ' R'.$data['product_offer_value'];
        }

        if($data['product_offer_postfix'])
        {
            $name .= ' '.$data['product_offer_postfix'];
        }

        if($data['product_variation_postfix'])
        {
            $name .= ' '.$data['product_variation_postfix'];
        }

        if($data['product_modification_postfix'])
        {
            $name .= ' '.$data['product_modification_postfix'];
        }

        if(isset($productAttributes))
        {
            /** Добавляем к названию назначение */
            $Type = new TypeOzonProductsAttribute();

            foreach($productAttributes as $productAttribute)
            {
                if($Type::equals($productAttribute->id))
                {
                    $value = $Type::getConvertName($productAttribute->value);

                    if(!empty($value))
                    {
                        $name .= ' '.$value;
                    }
                }
            }
        }

        return empty($name) ? null : trim($name);
    }

    /**
     * Возвращает значение по умолчанию
     */
    public function default(): string|bool
    {
        return false;
    }

    /**
     * Метод указывает, нужно ли добавить свойство для заполнения в форму
     */
    public function isSetting(): bool
    {
        return false;
    }


    public function required(): bool
    {
        return false;
    }

    public static function priority(): int
    {
        return 100;
    }

    /**
     * Проверяет, относится ли значение к данному объекту
     */
    public static function equals(string $param): bool
    {
        return self::PARAM === $param;
    }

    public function choices(): bool
    {
        return false;
    }

}
