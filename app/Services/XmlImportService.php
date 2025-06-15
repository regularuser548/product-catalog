<?php

namespace App\Services;

use App\Models\Parameter;
use App\Models\ParameterValue;
use App\Models\Product;
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
    private int $total = 0;

    public function import(string $path): int
    {
        try {
            $reader = XmlReader::fromFile($path);

            $offers = $reader->element('offer')->lazy();

            /** @var Element $offer */
            foreach ($offers as $offer) {
                info('Importing offer #' . $this->count . ' of ' . $this->total);

                /** @var Element[] $data */
                $data = $offer->getContent();

                $product['id'] = (int)$offer->getAttribute('id');
                $product['name'] = $data['name']->getContent();
                $product['price'] = (float)$data['price']->getContent();
                $product['description'] = $data['description']->getContent();
                $product['available'] = (bool)$offer->getAttribute('available');
                $product['stock'] = (int)$data['stock_quantity']->getContent();

                $productModel = Product::create($product);

                /** @var Element[] $params */
                $params = $data['param']->getContent();
                foreach ($params as $param) {
                    $name = $param->getAttribute('name');
                    $slug = Str::slug($name);
                    $value = $param->getContent();

                    $paramModel = Parameter::firstOrCreate(
                        ['slug' => $slug],
                        ['name' => $name, 'slug' => $slug]
                    );

                    $paramValueModel = ParameterValue::firstOrCreate(
                        ['value' => $value],
                        ['parameter_id' => $paramModel->id, 'value' => $value]
                    );

                    $exists = DB::table('product_parameters')->where('product_id', $productModel->id)
                        ->where('parameter_value_id', $paramValueModel->id)->exists();

                    if (!$exists)
                        DB::table('product_parameters')->insert(['product_id' => $productModel->id,
                            'parameter_value_id' => $paramValueModel->id]);
                }

                $this->count++;
            }
        } catch (Throwable $e) {
            error($e->getMessage());
        }

        return 0;
    }

}
