<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\Request;

class FilterApiController extends Controller
{

    public function currencies(Request $request)
    {
        $term = $request->input('q');

        return $this->searchModel(Currency::class, ['name', 'iso', 'exchange_rate'], $term);

    }

    private function searchModel(string $model, array $fields, string $term = null)
    {
        if ($term) {
            $clause = [];
            foreach ($fields as $f) {
                $clause[] = [$f, "LIKE", '%' . $term . '%'];
            }
            if (sizeOf($clause)) {
                $results = $model::when(sizeOf($clause), function ($query) use ($clause) {
                    $query->where(...$clause[0]);
                    $k = array_slice($clause, 1);
                    foreach ($k as $s) {
                        $query->orWhere(...$s);
                    }

                    return $query;
                })->paginate(10);
            } else {
                $results = Currency::paginate(10);
            }

        } else {
            $results = Currency::paginate(10);
        }

        return $results;

    }

    public function users(Request $request)
    {
        $term = $request->input('q');

        return $this->searchModelUser(User::class, ['first_name', 'last_name', 'email', 'phone'], $term);

    }

    private function searchModelUser(string $model, array $fields, string $term = null)
    {
        if ($term) {
            $clause = [];
            foreach ($fields as $f) {
                $clause[] = [$f, "LIKE", '%' . $term . '%'];
            }
            if (sizeOf($clause)) {
                $results = $model::when(sizeOf($clause), function ($query) use ($clause) {
                    $query->where(...$clause[0]);
                    $k = array_slice($clause, 1);
                    foreach ($k as $s) {
                        $query->orWhere(...$s);
                    }

                    return $query;
                })->paginate(10);
            } else {
                $results = User::paginate(10);
            }

        } else {
            $results = User::paginate(10);
        }
        $results->append('name');
        $results->append('name_with_email');

        return $results;

    }

}
