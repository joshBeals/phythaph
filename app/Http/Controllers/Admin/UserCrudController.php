<?php

namespace App\Http\Controllers\Admin;

use App\Classes\GlobalVars;
use App\Classes\Helper;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\SubscriptionPlans;
use App\Models\Transaction;
use App\Models\User;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    use ShowOperation {show as traitShow;}

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/customer');
        CRUD::setEntityNameStrings('customer', 'customers');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->query = $this->crud->query->withTrashed();
        CRUD::column('id')->type('text')->label('Customer ID');
        CRUD::column('first_name');
        CRUD::column('last_name');
        CRUD::column('email');
        CRUD::column('account_type');
        CRUD::column('created_at');
        $this->crud->addFilter(
            [
            'type' => 'simple',
            'name' => 'trashed',
            'label'=> 'Deleted Customers',
            ],
            false,
            function ($values) {
                // if the filter is active
                $this->crud->query = $this->crud->query->onlyTrashed();
            }
        );

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(UserRequest::class);

        CRUD::field('first_name');
        CRUD::field('last_name');
        CRUD::field('email');
        CRUD::field('password');
        CRUD::field('account_type')->type('enum');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function show($id)
    {
        // custom logic before
        $content = $this->traitShow($id);

        $this->data['entry']->decorate();
        // cutom logic after
        return view("backpack::customer.show", $this->data);

    }

    public function fundWallet(Request $request){

        $inputs = $request->except(['_token', '_method']);

        $validate = Validator::make($inputs, [
            'user_id' => 'required',
            'amount' => 'required',
        ]);

        if ($validate->fails()) {
            return redirect(url()->previous() . '#wallet')->with('error_message', 'Invalid form fields, please check your inputs');
        }

        $user = User::findOrFail($request->user_id);
        
        $obj = new \StdClass;

        $amount = $request->amount;

        $obj->description = "Bank Transfer";
        $obj->user_id = $user->id;
        $obj->amount = $amount;
        $obj->type = 'wallet_topup';
        $obj->scope = 'wallet_topup';

        $txn = Transaction::initialize(floatval($amount), $obj);

        $save = $user->depositToWallet($amount, $obj->description, $txn);

        return redirect('/admin/customer/' . $user->id . '/show#wallet');
    }

    public function subscribe(Request $request)
    {

        $inputs = $request->except(['_token', '_method']);

        $validate = Validator::make($inputs, [
            'user_id' => 'required',
            'plan_id' => 'required',
            'years' => 'required',
            'from' => 'required',
        ]);

        if ($validate->fails()) {
            return redirect(url()->previous() . '#subscription')->with('error_message', 'Invalid form fields, please check your inputs');
        }

        $user = User::findOrFail($request->user_id);

        $plan = SubscriptionPlans::findOrFail($request->plan_id);

        $amount = $user->hasSubscribedOnce() ? $plan->renewal_fee : $plan->signon_fee;

        $obj = new \StdClass;

        $obj->description = "Customer Membership Subscription";
        $obj->user_id = $user->id;
        $obj->new_user = $user->hasSubscribedOnce();
        $obj->amount = $amount;
        $obj->type = 'membership_subscription';
        $obj->scope = 'membership_subscription';
        $obj->years = 1;

        $txn = Transaction::initialize(floatval($amount), $obj);

        $from = Carbon::parse($request->from);

        $save = $user->addSubscription($txn, $request->years, $from, $plan, $user->hasSubscribedOnce());

        return redirect('/admin/customer/' . $user->id . '/show#subscription');
    }
}
