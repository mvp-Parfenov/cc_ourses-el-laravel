<?php

namespace App\Http\Controllers\Admin\Adverts;

use App\Entity\Adverts\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use function redirect;

class CategoryController extends Controller
{

    public function __construct()
    {
        $this->middleware('can:manage-adverts-categories');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = Category::defaultOrder()->withDepth()->get();

        return view('admin.adverts.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

         $parents = Category::defaultOrder()->withDepth()->get();

        return view('admin.adverts.categories.create', compact('parents'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'parent' => 'nullable|integer|exists:advert_categories,id',
        ]);
        $category = Category::create([
            'name' => $request['name'],
            'slug' => $request['slug'],
            'parent_id' => $request['parent'],
        ]);
        return redirect()->route('admin.adverts.categories.show', $category);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Entity\Adverts\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        $parentAttributes = $category->parentAttributes();
        $attributes = $category->attributes()->orderBy('sort')->get();

        return view('admin.adverts.categories.show', compact('category','attributes', 'parentAttributes'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Entity\Adverts\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        $parents = Category::defaultOrder()->withDepth()->get();
        return view('admin.adverts.categories.edit', compact('category', 'parents'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Entity\Adverts\Category $category
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Category $category)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'parent' => 'nullable|integer|exists:advert_categories,id',
        ]);
        $category->update([
            'name' => $request['name'],
            'slug' => $request['slug'],
            'parent_id' => $request['parent'],
        ]);
        return redirect()->route('admin.adverts.categories.show', $category);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Entity\Adverts\Category $category
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('admin.adverts.categories.index');
    }

    public function first(Category $category)
    {
        if($first = $category->siblings()->defaultOrder()->first()){
            $category->insertBeforeNode($first);
        }

        return redirect()->route('admin.adverts.categories.index');
    }

    public function up(Category $category)
    {
        $category->up();

        return redirect()->route('admin.adverts.categories.index');
    }

    public function down(Category $category)
    {
        $category->down();

        return redirect()->route('admin.adverts.categories.index');
    }

    public function last(Category $category)
    {
        if($last = $category->siblings()->defaultOrder('desc')->first()){
            $category->insertAfterNode($last);
        }

        return redirect()->route('admin.adverts.categories.index');
    }
}
