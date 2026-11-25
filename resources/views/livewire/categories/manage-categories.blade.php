<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>
    @endif

    <div class="catalog-panel-heading category-page-heading"><div><h2>Daftar kategori</h2><p>Atur kategori dan buka atribut hanya saat dibutuhkan.</p></div><button class="button-primary" wire:click="createCategory" type="button">+ Tambah kategori</button></div>
        <section class="catalog-panel">
            <input class="catalog-input mb-4" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kategori...">
            @error('category')<p class="catalog-error mb-3">{{ $message }}</p>@enderror
            <div class="divide-y divide-neutral-100">
                @forelse ($categories as $category)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="category-{{ $category->id }}">
                        <div><div class="font-medium text-neutral-900">{{ $category->name }}</div><div class="mt-1 text-xs text-neutral-500">{{ $category->products_count }} produk · {{ $category->specifications->count() }} atribut</div></div>
                        <div class="flex flex-wrap gap-2">
                            <button class="button-secondary button-small" wire:click="openSpecifications({{ $category->id }})" type="button">Atribut</button>
                            <button class="button-secondary button-small" wire:click="editCategory({{ $category->id }})" type="button">Ubah</button>
                            <button class="button-danger button-small" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Hapus kategori {{ $category->name }}?" type="button">Hapus</button>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-neutral-500">Belum ada kategori.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $categories->links() }}</div>
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
                            <p class="mb-3 text-xs text-neutral-500">Isi pilihan satu per baris. Semua kolom boleh dikosongkan dan pilihan di Kasir juga tidak wajib dipilih.</p>
                            <label>Bentuk<textarea wire:model="dimensionOptions.shape" rows="4" placeholder="Contoh: Bulat&#10;Kotak">{{ $dimensionOptions['shape'] }}</textarea>@error('dimensionOptions.shape')<small>{{ $message }}</small>@enderror</label>
                            <label>Panjang<textarea wire:model="dimensionOptions.length" rows="4" placeholder="Contoh: 6 m&#10;12 m">{{ $dimensionOptions['length'] }}</textarea>@error('dimensionOptions.length')<small>{{ $message }}</small>@enderror</label>
                            <label>Lebar<textarea wire:model="dimensionOptions.width" rows="4" placeholder="Contoh: 2 cm&#10;4 cm">{{ $dimensionOptions['width'] }}</textarea>@error('dimensionOptions.width')<small>{{ $message }}</small>@enderror</label>
                            <label>Diameter besi<textarea wire:model="dimensionOptions.diameter" rows="4" placeholder="Contoh: Ø8 mm&#10;Ø10 mm&#10;Ø12 mm">{{ $dimensionOptions['diameter'] }}</textarea>@error('dimensionOptions.diameter')<small>{{ $message }}</small>@enderror</label>
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
