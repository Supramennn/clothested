<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categories · ClothesTed')]
class Categories extends Component
{
    public ?int $editingCategoryId = null;

    public string $name = '';

    public string $slug = '';

    public string $description = '';

    public ?string $notice = null;

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->withCount('products')->ordered()->get();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($this->editingCategoryId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingCategoryId === null) {
            Category::query()->create($validated);
            $this->notice = 'Category created.';
        } else {
            Category::query()->findOrFail($this->editingCategoryId)->update($validated);
            $this->notice = 'Category updated.';
        }

        unset($this->categories);
        $this->resetForm();
    }

    public function editCategory(int $categoryId): void
    {
        $category = Category::query()->findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->notice = null;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->notice = null;
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::query()->withCount('products')->findOrFail($categoryId);

        if ($category->products_count > 0) {
            $this->addError('categories', 'Move or remove the category products before deleting it.');

            return;
        }

        $category->delete();
        unset($this->categories);
        $this->notice = 'Category deleted.';
    }

    private function resetForm(): void
    {
        $this->reset('editingCategoryId', 'name', 'slug', 'description');
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.admin.catalog.categories');
    }
}
