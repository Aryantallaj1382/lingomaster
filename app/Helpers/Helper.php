<?php

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;

if (!function_exists('to_latin_digits')) {
    function to_latin_digits(string|null $str): string|null
    {
        if (blank($str))
            return $str;

        return str_replace(
            array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'),
            array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
            $str
        );
    }
}

if (!function_exists('persian_digits')) {
    function to_persian_digits(string|int|float|null $str): string|null
    {
        if ($str === null)
            return null;

        return str_replace(
            array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
            array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'),
            (string)$str
        );
    }
}
if (!function_exists('generateOrderCode')) {
    function generateOrderCode($userId)
    {
        $random = random_int(100, 999);
        return 1 . $random . str_pad($userId, 5, '0', STR_PAD_LEFT) . time();
    }
}

use Illuminate\Pagination\LengthAwarePaginator;

function api_response(mixed $data = [], string $message = '', int $status = 200, array $append = []): JsonResponse
{
    $append['message'] = $message;
    // Check if the data is paginated
    if ($data instanceof LengthAwarePaginator) {
        $append['response'] = [
            'current_page' => $data->currentPage(),
            'data' => $data->items(),
            'first_page_url' => $data->url(1),
            'from' => $data->firstItem(),
            'last_page' => $data->lastPage(),
            'last_page_url' => $data->url($data->lastPage()),
            'links' => generatePaginationLinks($data),
            'next_page_url' => $data->nextPageUrl(),
            'path' => $data->path(),
            'per_page' => $data->perPage(),
            'prev_page_url' => $data->previousPageUrl(),
            'to' => $data->lastItem(),
            'total' => $data->total(),
        ];
    } else {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        $append['response'] = [
            'data' => $data,
        ];
    }

    return response()->json($append, $status);
}

function generatePaginationLinks(LengthAwarePaginator $data)
{
    $links = [];
    $links[] = [
        'url' => $data->previousPageUrl(),
        'label' => '&laquo; Previous',
        'active' => $data->onFirstPage(),
    ];
    foreach (range(1, $data->lastPage()) as $page) {
        $links[] = [
            'url' => $data->url($page),
            'label' => (string) $page,
            'active' => $data->currentPage() === $page,
        ];
    }

    $links[] = [
        'url' => $data->nextPageUrl(),
        'label' => 'Next &raquo;',
        'active' => !$data->hasMorePages(),
    ];

    return $links;
}


function normalize_filename(string $filename): string
{
    $replacements = [
        'тАУ' => '-',
    ];

    return strtr($filename, $replacements);
}
function getRates()
{
    return Cache::remember('currency_rates', 1800, function () { // 1800 ثانیه = 30 دقیقه
        $response = Http::get('https://brsapi.ir/Api/Market/Gold_Currency.php?key=BAgwgwda6bntIUxQyXgMr5y4ar5Nc8rp');
        if ($response->failed()) {
            return null;
        }


        $data = $response->json();

        // فقط دلار و یورو رو نگه داریم
        $filtered = collect($data['currency'] ?? [])
            ->whereIn('symbol', ['USD', 'EUR'])
            ->mapWithKeys(fn ($item) => [
                $item['symbol'] => [
                    'price' => $item['price'],
                    'unit' => $item['unit'],
                    'date' => $item['date'],
                    'time' => $item['time']
                ]
            ])->toArray();

        return $filtered;
    });

}

function convert($amount, $from = 'USD', $to = 'IRT')
{
    $rates = getRates();

    if (!$rates || !isset($rates['USD']) || !isset($rates['EUR'])) {
        return null;
    }

    $usdToToman = $rates['USD']['price'];
    $eurToToman = $rates['EUR']['price'];

    switch (strtoupper($from)) {
        case 'USD':
            $inToman = $amount * $usdToToman;
            break;

        case 'EUR':
            $inToman = $amount * $eurToToman;
            break;

        case 'IRT':
            $inToman = $amount;
            break;

        case 'IRR':
            $inToman = $amount / 10;
            break;

        default:
            return null;
    }

    switch (strtoupper($to)) {
        case 'USD':
            return $inToman / $usdToToman;

        case 'EUR':
            return $inToman / $eurToToman;

        case 'IRT':
            return $inToman;

        case 'IRR': // تومان به ریال
            return $inToman * 10;

        default:
            return null;
    }
}
