<?php

namespace App\Classes;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Helper
{

    public static $successDefaultMessage = 'Request successfull';
    public static $failDefaultMessage = 'Request unsuccessfull';

    public static function arrayToObject($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        $object = new \stdClass();
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $name => $value) {
                $name = strtolower(trim($name));
                if (!empty($name)) {
                    $object->$name = Helper::arrayToObject($value);
                }
            }
            return $object;
        } else {
            return false;
        }
    }

    public static function formatNumber($num)
    {
        return number_format(doubleval($num), 2, ".", ",");
    }

    public static function formatToCurrency($num, $currency = null)
    {
        return ($currency ? $currency : GlobalVars::NAIRA) . Self::formatNumber($num);
    }

    public static function readableDate($date)
    {
        return Carbon::now()->parse($date)->diffForHumans();
    }

    /**
     * Format date like 10th September 2019
     *
     * @param string|Carbon $date
     *
     * @return string
     */
    public static function formatDate($date): string
    {
        return Carbon::parse($date)->format('D jS, F Y');
    }

    /**
     * Format date like 10 Sep. 2019
     *
     * @param string|Carbon $date
     *
     * @return string
     */
    public static function shortDate($date): string
    {
        return Carbon::parse($date)->format('d M Y');
    }

    /**
     * Get the date difference from now to a past date;
     *
     * @param mixed $date       The said date
     * @param bool $absolute    Alloe nwgative value
     *
     * @return int
     */
    public static function dateDiff($date, $absolute = true)
    {
        if ($absolute) {
            return Carbon::now()->diffInDays(Carbon::parse($date), $absolute);
        }

        return now()->floatDiffInDays(Carbon::parse($date), false);

    }

    public static function apiSuccess($data, $message = 'Request successfull')
    {

        $resp = [
            'status' => true,
            'success' => true,
            'message' => $message,
        ];
        if ($data || is_array($data)) {
            $resp['data'] = $data;
        }

        return response()->json($resp, 200);
    }

    public static function apiFail($message = 'Request unsuccessful', $code = 400, $data = null, $errors = null)
    {
        $resp = [
            'status' => false,
            'success' => false,
            'message' => $message,
        ];

        if ($data) {
            $resp['data'] = $data;
        }

        if ($errors) {
            $errors = json_decode(json_encode($errors), true);
            if (is_array($errors) && sizeOf($errors) > 0) {
                $resp['errors'] = $errors;
            }
        }
        return response()->json($resp, (int) $code);

    }

    /**
     * Throw exceptions in a sane way
     *
     * @param \Throwable $e
     * @return object
     */
    public static function apiException(\Throwable $e)
    {
        Log::error($e);

        if (config('app.debug')) {
            return Helper::apiFail($e->getMessage() ?? 'Something went wrong, Please contact support!');
        }
        return Helper::apiFail('Something went wrong, Please contact support!');
    }

    public static function successApiOrRoute(array $api, $route = null)
    {
        $default = [
            'data' => null,
            'message' => static::$successDefaultMessage,
        ];

        $data = array_merge($default, $api);

        if (Request::ajax()) {
            return Self::apiSuccess($data['data'], $data['message']);
        }
        if ($route) {
            if (is_array($route)) {
                if (sizeOf($route) <= 1) {
                    return Redirect::to(URL::previous() . $route[0]);
                }
                return redirect()->route($route[0], $route[1]);
            }
            return redirect()->route($route);
        }

        return redirect()->back();
    }

    public static function failApiOrRoute(array $api, $route)
    {
        $default = [
            'data' => null,
            'message' => static::$failDefaultMessage,
            'code' => 400,
            'errors' => [],
        ];

        $data = array_merge($default, $api);

        if (Request::ajax()) {
            return Self::apiFail($data['message'], $data['code'], $data['data'], $data['errors'] ?? null);
        }

        if ($route) {
            if (is_array($route)) {
                if (sizeOf($route) <= 1) {
                    return Redirect::to(URL::previous() . $route[0])->withErrors($data['errors'])->withInput();
                }
                return redirect()->route($route[0], $route[1])->withErrors($data['errors'])->withInput();
            }
            return redirect()->route($route)->withErrors($data['errors'])->withInput();
        }
        return redirect()->back()->withErrors($data['errors'])->withInput();

    }

    /**
     *  Format Illuminate\Paginator\LengthAwarePaginator for API calls so that number links can be present
     *  @param LengthAwarePaginator $paginator
     *  @param int|null $sides The numbers of links to add to eack sides;
     *
     *  @return array formated array ready to be sent as JSON
     */
    public static function formatApiPaginationData(LengthAwarePaginator $paginator, int $sides = 3): array
    {
        $paginator->onEachSide($sides);

        $window = UrlWindow::make($paginator);

        $links = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);

        $data = $paginator->toArray();
        $data['links'] = $links;

        return $data;

    }

    /**
     *  Format Illuminate\Paginator\LengthAwarePaginator for API calls so that number links can be present
     *  @param LengthAwarePaginator $paginator
     *  @param int|null $sides The numbers of links to add to eack sides;
     *
     *  @return array formated array ready to be sent as JSON
     */
    public static function formatApiPagination(LengthAwarePaginator $paginator, int $sides = 3): array
    {

        $return = [
            'success' => true,
            'data' => static::formatApiPaginationData($paginator, $sides),
        ];

        return $return;

    }

    /**
     * Convert use Illuminate\Database\Eloquent\Collection to \Illuminate\Pagination\LengthAwarePaginator
     * @param Collection $collection, the collection in question
     * @param int $perPage Items per page default to 20
     * @param string $pageName the identifier for the page default to 'page'
     * @param string $fragment
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function paginateCollection(Collection $collection, int $perPage = 20, string $pageName = 'page', $fragment = null): LengthAwarePaginator
    {
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage($pageName);
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage);
        parse_str(request()->getQueryString(), $query);
        unset($query[$pageName]);
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'pageName' => $pageName,
                'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
                'query' => $query,
                'fragment' => $fragment,
            ]
        );

        return $paginator;
    }

    public static function getJWTLoggedinUser()
    {
        $message = "Your session may have expired, please logout and log back in";
        try {

            if (!$admin = \JWTAuth::parseToken()->authenticate()) {
                return response()->json(['message' => $message], 403);
            }

            return $admin;

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['message' => $message], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['message' => $message], $e->getStatusCode());

        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['message' => $message], $e->getStatusCode());

        }
    }

    /**
     * Get external api and store it into cache for 24 hours
     * Useful for api where external api that are refreshed once daily
     */
    public static function getCachedExternalApi(string $endpoint, string $key)
    {
        $api = app('\App\Classes\GenericApi');
        if (cache()->has($key)) {
            return response()->json(cache()->get($key));
        }

        try {
            $get = $api->send($endpoint);
            cache()->put($key, json_decode($get), now()->addhours(24));
            return $get;
        } catch (\Throwable $e) {
            return Self::apiFail('Cannot fetch api ' . $endpoint);
        }
    }

    /**
     * Get the daily quotes from quotes.rest
     *
     * @return Illuminate\Http\Response;
     */
    public static function getQuotes()
    {
        return static::getCachedExternalApi('https://quotes.rest/qod.json', 'qod');
    }

    /**
     * Get the vistors IP address
     *
     * @return string
     */
    public static function getIp()
    {
        if (request()->headers->has('cf-connecting-ip')) {
            $h = request()->headers->get('cf-connecting-ip');

            return is_array($h) ? $h[0] : $h;
        }

        return request()->ip();
    }

    /**
     * Generate transaction reference
     *
     * @param int $chars = 10
     *
     * @return string
     */
    public static function makeTxnRef(int $chars = 10): string
    {
        $ref = RandomGenerator::getHashedToken($chars);
        return strtolower(config('app.iso_name') . $ref);
    }

    /**
     * Convert numbers to short format with letter representation
     *
     * @param float $num
     * @return string
     */
    public static function shortNumber(float $num): string
    {
        $units = ['', 'K', 'M', 'B', 'T'];
        for ($i = 0; $num >= 1000; $i++) {
            $num /= 1000;
        }
        return round($num, 1) . $units[$i];
    }

    /**
     * Convert a string to title case
     *
     * @param string $string    The string to convert
     *
     * @return string
     */
    public static function titleCase(string $string): string
    {
        return Str::title(str_replace("_", " ", $string));
    }

    /**
     * Convert an array to assiciative array
     * with key as the array value and value as title case
     *
     * @param array $array
     *
     * @return array
     */
    public static function arrayOfValueTitle(array $array): array
    {
        $options = [];

        foreach ($array as $opt) {
            $options[$opt] = Self::titleCase($opt);
        }

        return $options;
    }

    /**
     * Get the status color code to use for UI
     *
     * @param string $status    The status
     *
     * @return string
     */
    public static function statusColorCode(string $status): ?string
    {
        $color = '';
        switch ($status) {
            case 'pending':
            case 'unpaid':
                $color = 'warning';
                break;
            case 'approved':
            case 'authorized':
            case 'success':
            case 'running':
            case 'completed':
            case 'paid':
            case 'active':
            case 'processed':
                $color = 'success';
                break;
            case 'declined':
            case 'cancelled':
            case 'overdue':
            case 'missed':
                $color = 'danger';
                break;
            default:
                $color = 'info';
                break;
        }

        return $color;
    }

    /**
     * Get the day ordinal of day of the month
     * @param int $number   The day of the month
     *
     * @return string
     */
    public static function dayOfTheMonthOrdinal(int $number): string
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return 'th';
        } else {
            return $ends[$number % 10];
        }

    }

    /**
     * Get the friendly name of tenor units
     *  @param string $unit     The unit to compute
     *
     *  @return string
     */
    public static function getTenorUnit(string $unit): string
    {

        $calc = "days";

        switch (\strtolower($unit)) {
            case 'per_annum':
                $calc = "years";
                break;
            case 'per_month':
                $calc = "months";
                break;
            default:
                $calc = "days";
                break;
        }

        return $calc;
    }

}
