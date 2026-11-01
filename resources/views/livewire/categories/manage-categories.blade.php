<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm text-neutral-800">{{ session('status') }}</div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.8fr)]">
        @if ($canManage)
            <section class="catalog-panel">
                <div class="catalog-panel-heading"><div><h2>{{ $editingCategoryId ? 'Ubah kategori' : 'Tambah kategori' }}</h2><p>Kelompokkan material dan produk konstruksi.</p></div></div>
                <form wire:submit="saveCategory" class="catalog-form">
                    <label>Nama kategori<input wire:model="name" type="text" maxlength="120" placeholder="Contoh: Besi Konstruksi">@error('name')<small>{{ $message }}</small>@enderror</label>
                    <label>Deskripsi<textarea wire:model="description" rows="3" placeholder="Keterangan singkat (opsional)"></textarea>@error('description')<small>{{ $message }}</small>@enderror</label>
                    <div class="flex gap-2"><button class="button-primary" type="submit">{{ $editingCategoryId ? 'Simpan perubahan' : 'Simpan kategori' }}</button>@if($editingCategoryId)<button class="button-secondary" wire:click="cancelCategoryEdit" type="button">Batal</button>@endif</div>
                </form>
            </section>
        @endif

        <section class="catalog-panel">
            <div class="catalog-panel-heading"><div><h2>Daftar kategori</h2><p>Kategori menjadi templat atribut produk.</p></div></div>
            <input class="catalog-input mb-4" wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kategori...">
            @error('category')<p class="catalog-error mb-3">{{ $message }}</p>@enderror
            <div class="divide-y divide-neutral-100">
                @forelse ($categories as $category)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="category-{{ $category->id }}">
                        <div><div class="font-medium text-neutral-900">{{ $category->name }}</div><div class="mt-1 text-xs text-neutral-500">{{ $category->products_count }} produk · {{ $category->specifications->count() }} atribut</div></div>
                        <div class="flex flex-wrap gap-2">
                            <button class="button-secondary button-small" wire:click="openSpecifications({{ $category->id }})" type="button">Atribut</button>
                            @if ($canManage)
                                <button class="button-secondary button-small" wire:click="editCategory({{ $category->id }})" type="button">Ubah</button>
                                <button class="button-danger button-small" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Hapus kategori {{ $category->name }}?" type="button">Hapus</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-sm text-neutral-500">Belum ada kategori.</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $categories->links() }}</div>
        </section>
    </div>

    @if ($selectedCategory)
        <section class="catalog-panel">
            <div class="catalog-panel-heading"><div><h2>Atribut · {{ $selectedCategory->name }}</h2><p>Gunakan atribut berbeda untuk kebutuhan tiap jenis produk.</p></div><button class="button-secondary button-small" wire:click="$set('specificationCategoryId', null)" type="button">Tutup</button></div>
            @error('specification')<p class="catalog-error mb-3">{{ $message }}</p>@enderror
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(300px,0.8fr)]">
                <div class="divide-y divide-neutral-100">
                    @forelse ($selectedCategory->specifications as $specification)
                        <div class="flex flex-wrap items-center justify-between gap-3 py-3" wire:key="spec-{{ $specification->id }}"><div><strong class="text-sm">{{ $specification->name }}</strong><span class="ml-2 text-xs text-neutral-500">{{ $specification->data_type }}{{ $specification->measurement_unit ? ' · '.$specification->measurement_unit : '' }}{{ $specification->is_required ? ' · wajib' : '' }}</span></div>@if($canManage)<div class="flex gap-2"><button class="button-secondary button-small" wire:click="editSpecification({{ $specification->id }})" type="button">Ubah</button><button class="button-danger button-small" wire:click="deleteSpecification({{ $specification->id }})" wire:confirm="Hapus atribut {{ $specification->name }}?" type="button">Hapus</button></div>@endif</div>
                    @empty
                        <div class="py-7 text-center text-sm text-neutral-500">Belum ada atribut khusus.</div>
                    @endforelse
                </div>
                @if ($canManage)
                    <form wire:submit="saveSpecification" class="catalog-form rounded-lg border border-neutral-200 bg-neutral-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold">{{ $editingSpecificationId ? 'Ubah atribut' : 'Tambah atribut' }}</h3>
                        <label>Nama atribut<input wire:model="specificationName" type="text" placeholder="Contoh: Diameter besi">@error('specificationName')<small>{{ $message }}</small>@enderror</label>
                        <label>Kode atribut<input wire:model="specificationKey" type="text" placeholder="diameter_besi">@error('specificationKey')<small>{{ $message }}</small>@enderror</label>
                        <div class="grid grid-cols-2 gap-3"><label>Jenis data<select wire:model="specificationType"><option value="text">Teks</option><option value="number">Angka desimal</option><option value="integer">Bilangan bulat</option><option value="boolean">Ya / tidak</option></select>@error('specificationType')<small>{{ $message }}</small>@enderror</label><label>Satuan ukur<input wire:model="measurementUnit" type="text" placeholder="mm, cm, kg">@error('measurementUnit')<small>{{ $message }}</small>@enderror</label></div>
                        <label class="!flex !flex-row !items-center gap-2"><input class="!w-4" wire:model="specificationRequired" type="checkbox">Wajib diisi saat membuat produk</label>
                        @error('specificationCategoryId')<small class="catalog-error">{{ $message }}</small>@enderror
                        <div class="flex gap-2"><button class="button-primary" type="submit">Simpan atribut</button>@if($editingSpecificationId)<button class="button-secondary" wire:click="cancelSpecificationEdit" type="button">Batal</button>@endif</div>
                    </form>
                @endif
            </div>
        </section>
    @endif
</div>
