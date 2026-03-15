<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use Illuminate\Http\Request;

class MetadataController extends Controller
{
    private array $sortableMetaCols = ['id','category','value','description'];

    /**
     * List metadata by category.
     */
    public function index(Request $request)
    {
        $category = $request->input('category', '');
        $categories = Metadata::distinct()->pluck('category')->sort()->values();

        $sortCol = $this->resolveSort($request, $this->sortableMetaCols) ?: 'value';
        $sortDir = $this->resolveSortDir($request) ?: 'asc';
        $perPage = $this->resolvePerPage($request);

        $query = $category ? Metadata::byCategory($category) : Metadata::query();
        $records = $query->orderBy($sortCol, $sortDir)->paginate(
            $perPage > 0 ? $perPage : 9999
        )->withQueryString();

        return view('metadata.index', [
            'records'         => $records,
            'categories'      => $categories,
            'selectedCategory'=> $category,
            'user'            => $this->currentUser(),
            'currentSort'     => $sortCol,
            'currentDir'      => $sortDir,
            'currentPerPage'  => $perPage,
        ]);
    }

    /**
     * Edit form for a metadata item.
     */
    public function edit(?int $id = null)
    {
        $record = $id ? Metadata::findOrFail($id) : null;
        $categories = Metadata::distinct()->pluck('category')->sort()->values();

        return view('metadata.edit', [
            'record' => $record,
            'categories' => $categories,
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * Save a metadata item.
     */
    public function save(Request $request)
    {
        $request->validate([
            'category' => 'required',
            'value' => 'required',
        ]);

        $id = $request->input('id');
        $data = $request->only(['category', 'value', 'description']);

        if ($id) {
            Metadata::where('id', $id)->update($data);
        } else {
            Metadata::create($data);
        }

        return redirect()->route('metadata.index', ['category' => $request->input('category')])
            ->with('message_success', 'Metadata saved successfully.');
    }

    /**
     * Delete a metadata item.
     */
    public function delete(int $id)
    {
        Metadata::findOrFail($id)->delete();
        return redirect()->back()->with('message_success', 'Metadata item deleted.');
    }
}
