<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;

/**
 * Class CategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CategoryCrudController extends CrudController
{
    
    use ShowOperation {show as traitShow;}
    use CreateOperation {store as traitStore;}
    use UpdateOperation { update as traitUpdate; }
    
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Category::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/category');
        CRUD::setEntityNameStrings('category', 'categories');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('id')->type('text')->label('Category ID');
        CRUD::column('image')->type('image');
        CRUD::column('name');
        CRUD::column('description');
        CRUD::column('type');

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
        CRUD::setValidation(CategoryRequest::class);

        CRUD::field('name')->size(6);
        CRUD::field('type')->type('enum')->size(6);
        CRUD::field('description');
        CRUD::field('image')->type('upload')->upload(true)->label('Category Image');
        $this->crud->addField([
            'name' => 'requirements',
            'label' => 'Requirements',
            'type' => 'repeatable',
            'new_item_label' => 'Add Requirement',
            'subfields' => [
                [
                    'wrapper' => ['class' => 'form-group col-md-4'],
                    'name' => 'name',
                    'type' => 'text',
                    'label' => 'Requirement',
                ],
                [
                    'wrapper' => ['class' => 'form-group col-md-4'],
                    'name' => 'field',
                    'type' => 'select_from_array',
                    'label' => 'Field',
                    'options' => ['text' => 'Text', 'dropdown' => 'Dropdown']
                ],
                [ 
                    'wrapper' => ['class' => 'form-group col-md-4'],
                    'name' => 'options',
                    'label' => 'Options',
                    'type' => 'textarea',
                ]
            ],
        ]);
        $this->crud->addField([
            'name' => 'prices',
            'label' => 'Prices',
            'type' => 'repeatable',
            'new_item_label' => 'Add Price',
            'subfields' => [
                [
                    'wrapper' => ['class' => 'form-group col-md-6'],
                    'name' => 'name',
                    'type' => 'text',
                    'label' => 'Price Name',
                ],
                [
                    'wrapper' => ['class' => 'form-group col-md-6'],
                    'name' => 'currency_id',
                    'type' => 'select',
                    'model' => "App\Models\Currency",
                    'attribute' => 'name',
                ]
            ],
        ]);

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

    /**
     * Store a newly created resource in the database.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store()
    {
        $request = $this->crud->validateRequest();

        $this->crud->unsetValidation(); // validation has already been run

        $data = $request->validated();

        try {
            
            $img = Storage::disk('s3')->put('images', $data['image']);
            $img_url = Storage::disk('s3')->url($img);

            $category = Category::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? "",
                'requirements' => $data['requirements'] ?? "",
                'prices' => $data['prices'] ?? "",
                'image' => $img_url,
            ]);

            return redirect('/admin/category');

        } catch (\Throwable $th) {
            $message = "Error: " . \addslashes($th->getMessage());
            \Alert::error($message)->flash();
            return redirect(url()->previous());

        }
    }

    public function update($id)
    {
        $content = $this->traitUpdate($id);

        $request = $this->crud->validateRequest();

        $this->crud->unsetValidation(); // validation has already been run

        $data = $request->validated();

        try {
            
            if(array_key_exists('image', $data)){
                if($data['image'] != NULL){
                    $img = Storage::disk('s3')->put('images', $data['image']);
                    $img_url = Storage::disk('s3')->url($img);
                }
            }

            $category = Category::where('id', $id)->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? "",
                'requirements' => $data['requirements'] ?? "",
                'prices' => $data['prices'] ?? "",
                'image' => $img_url ?? $data['image'],
            ]);

            return redirect('/admin/category/'.$id.'/edit');

        } catch (\Throwable $th) {
            $message = "Error: " . \addslashes($th->getMessage());
            \Alert::error($message)->flash();
            return redirect(url()->previous());

        }


    }

    public function show($id)
    {
        // custom logic before
        $content = $this->traitShow($id);

        $this->data['entry']->decorate();
        // cutom logic after
        return view("backpack::category.show", $this->data);

    }
}
