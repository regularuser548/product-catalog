## Installation

**1. Get the app**:

Clone repo:

```bash
git clone https://github.com/regularuser548/product-catalog.git
```

**2. Install Dependencies**:

> This app uses Laravel Sail

```bash
cd product-catalog
alias sail='[ -f sail ] && sh sail || sh vendor/bin/sail'
cp .env.example .env
sail up -d
sail composer i && sail npm i && sail artisan key:generate && sail artisan storage:link && sail artisan migrate && sail artisan db:seed
```

**3. Open App**:
> http://localhost

**4. Run Product Import**:
```bash 
sail artisan import:xml "path_to_file"
```

**5. Product Endpoint Example**:

`http://localhost/api/catalog/products?filter[kolir][]=sinii&filter[kolir][]=chornii`

**6. Filters Endpoint Example**:

`http://localhost/api/catalog/filters?filter[kolir][]=sinii&filter[kolir][]=chornii`
