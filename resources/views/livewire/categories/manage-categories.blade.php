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
                        @forelse($savedDimensionSets as $setIndex => $dimensionSet)
                            <div class="flex items-center justify-between gap-3 py-3" wire:key="saved-dimension-set-{{ $setIndex }}">
                                <div>
                                    <strong class="text-sm">Ukuran {{ $setIndex + 1 }}</strong>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($dimensionSet as $field)
                                            <span class="rounded-md bg-neutral-100 px-2 py-1 text-xs text-neutral-600">{{ $field['label'] }}: @if($field['label'] === 'Harga per kg')@rupiah($field['value'])@else{{ $field['value'] }}@endif</span>
                                        @endforeach
                                    </div>
                                </div>
                                <button class="button-secondary button-small" wire:click="editDimensionSet({{ $setIndex }})" type="button">Ubah</button>
                            </div>
                        @empty
                            <div class="py-7 text-center text-sm text-neutral-500">Belum ada ukuran.</div>
                        @endforelse
                        @if($savedDiameterOptions->isNotEmpty())
                            <section class="py-3">
                                <strong class="text-sm">Diameter besi · harga per kg</strong>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($savedDiameterOptions as $diameterOption)
                                        <button class="rounded-full bg-neutral-100 px-3 py-1.5 text-xs text-neutral-700" wire:click="editDimensionSet({{ $diameterOption['index'] }})" type="button">{{ $diameterOption['diameter'] }} · @rupiah($diameterOption['price'])/kg</button>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                    <div class="space-y-4">
                        <form wire:key="dimension-form-{{ $dimensionFormKey }}" wire:submit="saveDimensionOptions" class="catalog-form rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                            <h3 class="mb-1 text-sm font-semibold">Bentuk dan ukuran barang</h3>
                            <p class="mb-3 text-xs text-neutral-500">Isi atribut ukuran, lalu simpan. Ukuran yang tersimpan bisa dipilih dari panel kiri untuk diedit.</p>
                            <div class="dimension-set-list">
                                @foreach(range(0, max(array_map('count', collect($dimensionOptions)->only(['shape', 'length', 'width'])->all())) - 1) as $optionIndex)
                                    <div class="dimension-set-card" wire:key="dimension-set-{{ $dimensionFormKey }}-{{ $optionIndex }}">
                                        <div class="dimension-set-card-heading"><strong>{{ $editingDimensionSetIndex !== null ? 'Ubah ukuran '.($editingDimensionSetIndex + 1) : 'Atribut ukuran' }}</strong>@if($editingDimensionSetIndex === null && max(array_map('count', collect($dimensionOptions)->only(['shape', 'length', 'width'])->all())) > 1)<button class="dimension-option-remove" wire:click="removeDimensionRow({{ $optionIndex }})" type="button" aria-label="Hapus ukuran">&times;</button>@endif</div>
                                        <div class="dimension-set-fields">
                                            @foreach(['shape' => ['label' => 'Bentuk', 'placeholder' => 'Contoh: Bulat'], 'length' => ['label' => 'Panjang', 'placeholder' => 'Contoh: 6 m'], 'width' => ['label' => 'Lebar', 'placeholder' => 'Contoh: 2 cm']] as $field => $definition)
                                                <label class="dimension-set-input">{{ $definition['label'] }}<input class="dimension-option-input" wire:key="dimension-input-{{ $dimensionFormKey }}-{{ $field }}-{{ $optionIndex }}" wire:model.live="dimensionOptions.{{ $field }}.{{ $optionIndex }}" type="text" maxlength="120" placeholder="{{ $definition['placeholder'] }}">@error('dimensionOptions.'.$field.'.'.$optionIndex)<small>{{ $message }}</small>@enderror</label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                                <div class="dimension-set-card" wire:key="diameter-list-{{ $dimensionFormKey }}">
                                    <div class="dimension-set-card-heading"><strong>Daftar diameter besi dan harga/kg</strong></div>
                                    @foreach(range(0, max(array_map('count', [$dimensionOptions['diameter'], $dimensionOptions['price']])) - 1) as $diameterIndex)
                                        <div class="dimension-set-fields items-end" wire:key="diameter-row-{{ $dimensionFormKey }}-{{ $diameterIndex }}">
                                            <label class="dimension-set-input">Diameter besi<input class="dimension-option-input" wire:model.live="dimensionOptions.diameter.{{ $diameterIndex }}" type="text" maxlength="120" placeholder="Contoh: Ø8 mm">@error('dimensionOptions.diameter.'.$diameterIndex)<small>{{ $message }}</small>@enderror</label>
                                            <label class="dimension-set-input">Harga per kg<input class="dimension-option-input" wire:model.live="dimensionOptions.price.{{ $diameterIndex }}" type="number" min="0" step="0.01" placeholder="Contoh: 25000">@error('dimensionOptions.price.'.$diameterIndex)<small>{{ $message }}</small>@enderror</label>
                                            @if($diameterIndex > 0 || filled($dimensionOptions['diameter'][$diameterIndex] ?? null))<button class="dimension-option-remove" wire:click="removeDiameterOption({{ $diameterIndex }})" type="button" aria-label="Hapus diameter">&times;</button>@endif
                                        </div>
                                    @endforeach
                                    <button class="dimension-option-add mt-3" wire:click="addDiameterOption" type="button">+ Tambah diameter</button>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if($editingDimensionSetIndex !== null)
                                    <button class="button-secondary" wire:click="cancelDimensionSetEdit" type="button">Batal edit</button>
                                @endif
                                <button class="dimension-option-add" wire:click="addDimensionRow" type="button">+ Tambah ukuran</button>
                            </div>
                            @error('dimensionOptions')<small>{{ $message }}</small>@enderror
                            @foreach(['shape', 'length', 'width', 'diameter', 'price'] as $field)
                                @error('dimensionOptions.'.$field)<small>{{ $message }}</small>@enderror
                            @endforeach
                            @error('specificationCategoryId')<small class="catalog-error">{{ $message }}</small>@enderror
                            <button class="button-primary" type="submit">{{ $editingDimensionSetIndex === null ? 'Simpan bentuk dan ukuran' : 'Simpan perubahan ukuran' }}</button>
                        </form>


                    </div>
                </div>
            </section>
        </div>
    @endif
</div>
