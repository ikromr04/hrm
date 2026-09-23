<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * The papers kept with a unit: handover acts, invoices, warranty cards. The
 * file itself lives on the public disk; the row remembers what it is.
 */
class EquipmentDocumentController extends Controller
{
    /** What a scan may be: a PDF or a photograph of the paper. */
    private const TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx'];

    public function store(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:10240', 'mimes:'.implode(',', self::TYPES)],
        ], attributes: [
            'title' => 'название',
            'note' => 'примечание',
            'file' => 'файл',
        ]);

        $file = $request->file('file');

        $equipment->documents()->create([
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
            'extension' => strtolower($file->getClientOriginalExtension()),
            'path' => $file->store("equipment/{$equipment->id}", 'public'),
        ]);

        return back();
    }

    public function destroy(Equipment $equipment, EquipmentDocument $document): RedirectResponse
    {
        abort_if($document->equipment_id !== $equipment->id, 404);

        // The row goes with the file; a dangling scan helps nobody.
        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back();
    }
}
