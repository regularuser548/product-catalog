<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Saloon\XmlWrangler\XmlReader;
use Saloon\XmlWrangler\Data\Element;
use Throwable;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class XmlImportService
{
    private int $count = 1;

    public function import(string $path): int
    {
        DB::beginTransaction();

        try {
            $reader = XmlReader::fromFile($path);

            $offers = $reader->element('offer')->lazy();

            /** @var Element $offer */
            foreach ($offers as $offer) {
                info('Importing offer #' . $this->count);

                //Invalid field marker
                $hasInvalidFields = false;

                /** @var Element[] $data */
                $data = $offer->getContent();

                $product['id'] = (int)$offer->getAttribute('id');
                $product['name'] = $data['name']->getContent();
                $product['price'] = (float)$data['price']->getContent();
                $product['description'] = $data['description']->getContent();
                $product['available'] = (bool)$offer->getAttribute('available');
                $product['stock'] = (int)$data['stock_quantity']->getContent();

                /** @var Element[] $params */
                $params = $data['param']->getContent();

                //Validate product fields
                foreach ($product as $field) {
                    if ($field === null || $field === '')
                        $hasInvalidFields = true;
                }
                //Validate parameter fields
                foreach ($params as $param) {
                    $name = $param->getAttribute('name');
                    $value = $param->getContent();
                    if ($name === null || $name === '' || $value === null || $value === '' || $value === "<>")
                        $hasInvalidFields = true;
                }

                if ($hasInvalidFields) {
                    error('Invalid fields detected, skipping.');
                    continue;
                }

                //Insert product
                $productId = DB::table('products')->insertGetId($product);

                foreach ($params as $param) {
                    $name = $param->getAttribute('name');
                    $slug = Str::slug($name);
                    $value = $param->getContent();
                    $valueSlug = Str::slug($value);

                    //Insert parameter name if new
                    $paramId = DB::table('parameters')->where('slug', $slug)->value('id');
                    if ($paramId === null)
                        $paramId = DB::table('parameters')->insertGetId(['name' => $name, 'slug' => $slug]);

                    //Insert parameter value if new
                    $paramValId = DB::table('parameter_values')->where('parameter_id', $paramId)
                        ->where('slug', $valueSlug)->value('id');
                    if ($paramValId === null)
                        $paramValId = DB::table('parameter_values')->insertGetId(['parameter_id' => $paramId,
                            'slug' => $valueSlug, 'value' => $value]);

                    //Insert product parameter relation if new
                    DB::table('product_parameters')->insertOrIgnore(['product_id' => $productId,
                        'parameter_value_id' => $paramValId]);

                }

                $this->count++;
            }


        } catch (Throwable $e) {
            error($e->getMessage());
            DB::rollBack();
            return 1;
        }

        DB::commit();
        return 0;
    }

}
