<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use Illuminate\Http\Request;

class MetadataController extends Controller
{
    /**
     * List metadata by category.
     */
    public function index(Request $request)
    {
        $category = $request->input('category', 'defectsUnloading');
        $categories = Metadata::distinct()->pluck('category')->sort()->values();
        $records = Metadata::byCategory($category)->orderBy('value')->get();

        return view('metadata.index', [
            'records' => $records,
            'categories' => $categories,
            'selectedCategory' => $category,
            'user' => $this->currentUser(),
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
