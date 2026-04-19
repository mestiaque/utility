<?php
namespace ME\Utility\Http\Controllers;

use Illuminate\Http\Request;
use ME\Http\Controllers\Controller;
use ME\Utility\Models\BajarListGroup;
use ME\Utility\Models\BajarListItem;

class BajarListController extends Controller
{
    public function index(Request $request)
    {
        $query = BajarListGroup::query();
        if ($request->search) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        $groups = $query->orderByDesc('created_at')->get();
        return view('utility::bajar-list.index', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'group_date' => 'nullable|date',
            'color' => 'nullable|string|max:7',
        ]);
        $group = BajarListGroup::create($request->only('title', 'group_date', 'color'));
        return redirect()->back()->with('success', 'Group created!');
    }

    public function update(Request $request, BajarListGroup $group)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'group_date' => 'nullable|date',
            'color' => 'nullable|string|max:7',
        ]);
        $group->update($request->only('title', 'group_date', 'color'));
        return redirect()->back()->with('success', 'Group updated!');
    }

    public function destroy(BajarListGroup $group)
    {
        $group->delete();
        return redirect()->back()->with('success', 'Group deleted!');
    }


    public function listIndex(BajarListGroup $group, Request $request)
    {
        $query = $group->items();
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('item_name', 'like', '%'.$request->search.'%')
                  ->orWhere('brand', 'like', '%'.$request->search.'%')
                  ->orWhere('source', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $items = $query->orderByDesc('created_at')->get();
        return view('utility::bajar-list.list', compact('group', 'items'));
    }

    public function listStore(BajarListGroup $group, Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,purchased,hold',
        ]);
        $group->items()->create($request->all());
        return redirect()->back()->with('success', 'Item added!');
    }

    public function listUpdate(BajarListGroup $group, BajarListItem $item, Request $request)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,purchased,hold',
        ]);
        $item->update($request->all());
        return redirect()->back()->with('success', 'Item updated!');
    }

    public function listDestroy(BajarListGroup $group, BajarListItem $item)
    {
        $item->delete();
        return redirect()->back()->with('success', 'Item deleted!');
    }


    public function listPrint(BajarListGroup $group, Request $request)
    {
        $query = $group->items();
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('item_name', 'like', '%'.$request->search.'%')
                  ->orWhere('brand', 'like', '%'.$request->search.'%')
                  ->orWhere('source', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        $items = $query->orderByDesc('created_at')->get();
        return view('utility::bajar-list.print', compact('group', 'items'));
    }

        // API method for inline update
    public function apiListUpdate(Request $request, $itemId)
    {
        $item = BajarListItem::findOrFail($itemId);
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,purchased,hold',
        ]);
        $item->update($validated);
        return response()->json(['success' => true, 'item' => $item]);
    }
}
