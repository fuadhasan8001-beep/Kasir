<div class="category-livewire">
    @if (session('status'))
        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>
    @endif

    <section class="catalog-panel category-directory-panel" aria-labelledby="category-list-title">
        <div class="category-directory-heading">
            <div class="category-directory-title">
                <span class="category-directory-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 6.5A2.5 2.5 0 0 1 5.5 4H10l2 2h6.5A2.5 2.5 0 0 1 21 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5v-11Z"/><path d="M3.5 9h17"/></svg></span>
                <div><h2 id="category-list-title">Daftar Kategori</h2><p>Atur kategori dan buka atribut hanya saat dibutuhkan.</p></div>
            </div>
            <button class="button-primary category-add-button" wire:click="createCategory" type="button"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14"/></svg>Tambah Kategori</button>
        </div>
        <label class="category-search">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/></svg>
            <input class="catalog-input" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kategori..." aria-label="Cari kategori">
        </label>
            @error('category')<p class="catalog-error mb-3">{{ $message }}</p>@enderror
            <div class="category-table-wrap">
                <table class="category-table">
                    <thead><tr><th scope="col">Kategori</th><th scope="col" class="category-actions-heading">Aksi</th></tr></thead>
                    <tbody>
                @forelse ($categories as $category)
                    <tr wire:key="category-{{ $category->id }}">
                        <td>
                            <div class="category-row-info">
                                <span class="category-row-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="m4 8 8-4 8 4-8 4-8-4Z"/><path d="m4 12 8 4 8-4M4 16l8 4 8-4M4 8v8m16-8v8m-8-4v8"/></svg></span>
                                <div><strong>{{ $category->name }}</strong><small>{{ $category->products_count }} produk <span aria-hidden="true">·</span> {{ $category->specifications->count() }} atribut</small></div>
                            </div>
                        </td>
                        <td>
                            <div class="category-row-actions">
                                <button class="button-secondary category-action-button" wire:click="openSpecifications({{ $category->id }})" type="button"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 7h10M18 7h2M4 17h2m4 0h10M14 4v6M8 14v6"/></svg>Atur</button>
                                <button class="button-secondary category-action-button" wire:click="editCategory({{ $category->id }})" type="button"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="m14 5 5 5M4 20l4.5-.9L20 7.6 16.4 4 4.9 15.5 4 20Z"/></svg>Ubah</button>
                                <button class="button-danger category-action-button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Hapus kategori {{ $category->name }}?" type="button"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>Hapus</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2"><div class="category-empty-state">Belum ada kategori.</div></td></tr>
                @endforelse
                    </tbody>
                </table>
            </div>
        <div class="category-pagination">{{ $categories->links() }}</div>
    </section>
    @if($showCategoryForm)
        <div class="catalog-modal-backdrop" wire:click.self="cancelCategoryEdit">
            <section class="catalog-modal category-form-modal" role="dialog" aria-modal="true" aria-labelledby="category-form-title">
                <div class="catalog-panel-heading"><div><h2 id="category-form-title">{{ $editingCategoryId ? 'Ubah kategori' : 'Tambah kategori' }}</h2><p>Kelompokkan material dan produk konstruksi.</p></div><button class="button-secondary button-small" wire:click="cancelCategoryEdit" type="button">Tutup</button></div>
                <form wire:submit="saveCategory" class="catalog-form">
                    <label>Nama kategori<input wire:model="name" type="text" maxlength="120" placeholder="Contoh: Besi Konstruksi">@error('name')<small>{{ $message }}</small>@enderror</label>
                    <label>Deskripsi<textarea wire:model="description" rows="3" placeholder="Keterangan singkat (opsional)"></textarea>@error('description')<small>{{ $message }}</small>@enderror</label>
                    <div class="flex gap-2"><button class="button-primary" type="submit">{{ $editingCategoryId ? 'Simpan perubahan' : 'Simpan kategori' }}</button><button class="button-secondary" wire:click="cancelCategoryEdit" type="button">Batal</button></div>
                </form>
            </section>
        </div>
    @endif

    @if ($selectedCategory)
        <div class="catalog-modal-backdrop" wire:click.self="$set('specificationCategoryId', null)">
            <section class="catalog-modal category-specifications-modal" role="dialog" aria-modal="true" aria-labelledby="category-specifications-title">
                <div class="catalog-panel-heading"><div><h2 id="category-specifications-title">Atribut · {{ $selectedCategory->name }}</h2><p>Gunakan atribut berbeda untuk kebutuhan tiap jenis produk.</p></div><button class="button-secondary button-small" wire:click="$set('specificationCategoryId', null)" type="button">Tutup</button></div>
                @error('specification')<p class="catalog-error mb-3">{{ $message }}</p>@enderror
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
                    <div class="divide-y divide-neutral-100">
                        @forelse ($selectedCategory->specifications as $specification)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="spec-{{ $specification->id }}"><div><strong class="text-sm">{{ $specification->name }}</strong><span class="ml-2 text-xs text-neutral-500">{{ $specification->data_type }}{{ $specification->measurement_unit ? ' · '.$specification->measurement_unit : '' }}{{ $specification->is_required ? ' · wajib' : '' }}{{ $specification->allow_custom ? ' · ukuran custom aktif' : '' }}</span>@if($specification->options)<small class="block mt-1 text-xs text-neutral-500">Pilihan: {{ implode(', ', $specification->options) }}</small>@endif</div><div class="flex gap-2"><button class="button-secondary button-small" wire:click="editSpecification({{ $specification->id }})" type="button">Ubah</button><button class="button-danger button-small" wire:click="deleteSpecification({{ $specification->id }})" wire:confirm="Hapus atribut {{ $specification->name }}?" type="button">Hapus</button></div></div>
                        @empty
                            <div class="py-7 text-center text-sm text-neutral-500">Belum ada atribut khusus.</div>
                        @endforelse
                    </div>
                    <div class="space-y-4">
                        <form wire:submit="saveDimensionOptions" class="catalog-form rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                            <h3 class="mb-1 text-sm font-semibold">Bentuk dan ukuran barang</h3>
                            <p class="mb-3 text-xs text-neutral-500">Tambahkan satu nilai pada setiap baris. Nomor urut dibuat otomatis dan daftar yang tersimpan bisa dibuka lagi untuk diedit.</p>
                            <div class="dimension-option-field">
                                <span class="dimension-option-title">Bentuk</span>
                                @foreach($dimensionOptions['shape'] as $optionIndex => $option)
                                    <div class="dimension-option-row" wire:key="dimension-shape-{{ $optionIndex }}">
                                        <span class="dimension-option-number">{{ $optionIndex + 1 }}</span>
                                        <div class="dimension-option-value"><input class="dimension-option-input" wire:model="dimensionOptions.shape.{{ $optionIndex }}" type="text" maxlength="120" placeholder="Contoh: Bulat">@error('dimensionOptions.shape.'.$optionIndex)<small>{{ $message }}</small>@enderror</div>
                                        @if(count($dimensionOptions['shape']) > 1)<button class="dimension-option-remove" wire:click="removeDimensionOption('shape', {{ $optionIndex }})" type="button" aria-label="Hapus bentuk nomor {{ $optionIndex + 1 }}">&times;</button>@endif
                                    </div>
                                @endforeach
                                <button class="dimension-option-add" wire:click="addDimensionOption('shape')" type="button">+ Tambah bentuk</button>
                                @error('dimensionOptions.shape')<small>{{ $message }}</small>@enderror
                            </div>
                            <div class="dimension-option-field">
                                <span class="dimension-option-title">Panjang</span>
                                @foreach($dimensionOptions['length'] as $optionIndex => $option)
                                    <div class="dimension-option-row" wire:key="dimension-length-{{ $optionIndex }}">
                                        <span class="dimension-option-number">{{ $optionIndex + 1 }}</span>
                                        <div class="dimension-option-value"><input class="dimension-option-input" wire:model="dimensionOptions.length.{{ $optionIndex }}" type="text" maxlength="120" placeholder="Contoh: 6 m">@error('dimensionOptions.length.'.$optionIndex)<small>{{ $message }}</small>@enderror</div>
                                        @if(count($dimensionOptions['length']) > 1)<button class="dimension-option-remove" wire:click="removeDimensionOption('length', {{ $optionIndex }})" type="button" aria-label="Hapus panjang nomor {{ $optionIndex + 1 }}">&times;</button>@endif
                                    </div>
                                @endforeach
                                <button class="dimension-option-add" wire:click="addDimensionOption('length')" type="button">+ Tambah panjang</button>
                                @error('dimensionOptions.length')<small>{{ $message }}</small>@enderror
                            </div>
                            <div class="dimension-option-field">
                                <span class="dimension-option-title">Lebar</span>
                                @foreach($dimensionOptions['width'] as $optionIndex => $option)
                                    <div class="dimension-option-row" wire:key="dimension-width-{{ $optionIndex }}">
                                        <span class="dimension-option-number">{{ $optionIndex + 1 }}</span>
                                        <div class="dimension-option-value"><input class="dimension-option-input" wire:model="dimensionOptions.width.{{ $optionIndex }}" type="text" maxlength="120" placeholder="Contoh: 2 cm">@error('dimensionOptions.width.'.$optionIndex)<small>{{ $message }}</small>@enderror</div>
                                        @if(count($dimensionOptions['width']) > 1)<button class="dimension-option-remove" wire:click="removeDimensionOption('width', {{ $optionIndex }})" type="button" aria-label="Hapus lebar nomor {{ $optionIndex + 1 }}">&times;</button>@endif
                                    </div>
                                @endforeach
                                <button class="dimension-option-add" wire:click="addDimensionOption('width')" type="button">+ Tambah lebar</button>
                                @error('dimensionOptions.width')<small>{{ $message }}</small>@enderror
                            </div>
                            <div class="dimension-option-field">
                                <span class="dimension-option-title">Diameter besi</span>
                                @foreach($dimensionOptions['diameter'] as $optionIndex => $option)
                                    <div class="dimension-option-row" wire:key="dimension-diameter-{{ $optionIndex }}">
                                        <span class="dimension-option-number">{{ $optionIndex + 1 }}</span>
                                        <div class="dimension-option-value"><input class="dimension-option-input" wire:model="dimensionOptions.diameter.{{ $optionIndex }}" type="text" maxlength="120" placeholder="Contoh: Ø8 mm">@error('dimensionOptions.diameter.'.$optionIndex)<small>{{ $message }}</small>@enderror</div>
                                        @if(count($dimensionOptions['diameter']) > 1)<button class="dimension-option-remove" wire:click="removeDimensionOption('diameter', {{ $optionIndex }})" type="button" aria-label="Hapus diameter besi nomor {{ $optionIndex + 1 }}">&times;</button>@endif
                                    </div>
                                @endforeach
                                <button class="dimension-option-add" wire:click="addDimensionOption('diameter')" type="button">+ Tambah diameter</button>
                                @error('dimensionOptions.diameter')<small>{{ $message }}</small>@enderror
                            </div>
                            @error('specificationCategoryId')<small class="catalog-error">{{ $message }}</small>@enderror
                            <button class="button-primary" type="submit">Simpan bentuk dan ukuran</button>
                        </form>

                        <details class="rounded-lg border border-neutral-200 p-4" @if($editingSpecificationId) open @endif>
                            <summary class="cursor-pointer text-sm font-semibold">Atribut lainnya</summary>
                            <form wire:submit="saveSpecification" class="catalog-form mt-4">
                                <h3 class="mb-3 text-sm font-semibold">{{ $editingSpecificationId ? 'Ubah atribut' : 'Tambah atribut' }}</h3>
                                <label>Nama atribut<input wire:model="specificationName" type="text" placeholder="Contoh: Ketebalan">@error('specificationName')<small>{{ $message }}</small>@enderror</label>
                                <label>Kode atribut<input wire:model="specificationKey" type="text" placeholder="ketebalan">@error('specificationKey')<small>{{ $message }}</small>@enderror</label>
                                <div class="grid grid-cols-2 gap-3"><label>Jenis data<select wire:model="specificationType"><option value="text">Teks</option><option value="number">Angka desimal</option><option value="integer">Bilangan bulat</option><option value="boolean">Ya / tidak</option></select>@error('specificationType')<small>{{ $message }}</small>@enderror</label><label>Satuan ukur<input wire:model="measurementUnit" type="text" placeholder="mm, cm, kg">@error('measurementUnit')<small>{{ $message }}</small>@enderror</label></div>
                                <label>Pilihan standar<textarea wire:model="specificationOptions" rows="3" placeholder="Satu pilihan per baris">{{ $specificationOptions }}</textarea>@error('specificationOptions')<small>{{ $message }}</small>@enderror</label>
                                <label class="!flex !flex-row !items-center gap-2"><input class="!w-4" wire:model="specificationRequired" type="checkbox">Wajib diisi saat membuat produk</label>
                                <label class="!flex !flex-row !items-center gap-2"><input class="!w-4" wire:model="specificationAllowCustom" type="checkbox">Izinkan nilai custom yang diketik manual di Kasir</label>
                                <div class="flex gap-2"><button class="button-primary" type="submit">Simpan atribut</button>@if($editingSpecificationId)<button class="button-secondary" wire:click="cancelSpecificationEdit" type="button">Batal</button>@endif</div>
                            </form>
                        </details>
                    </div>
                </div>
            </section>
        </div>
    @endif
</div>
