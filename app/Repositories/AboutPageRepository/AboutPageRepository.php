<?php

namespace App\Repositories\AboutPageRepository;

use App\Models\AboutPage;
use Illuminate\Pagination\LengthAwarePaginator;

class AboutPageRepository
{
    /**
     * Get all about pages with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return AboutPage::ordered()
            ->paginate($perPage);
    }

    /**
     * Get all active about pages with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getActivePaginated(int $perPage = 15): LengthAwarePaginator
    {
        return AboutPage::active()
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Get all published about pages with pagination.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return AboutPage::published()
            ->active()
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Find about page by ID.
     *
     * @param int $id
     * @return AboutPage|null
     */
    public function findById(int $id): ?AboutPage
    {
        return AboutPage::find($id);
    }

    /**
     * Find active about page by ID.
     *
     * @param int $id
     * @return AboutPage|null
     */
    public function findActiveById(int $id): ?AboutPage
    {
        return AboutPage::active()->find($id);
    }

    /**
     * Find published about page by ID.
     *
     * @param int $id
     * @return AboutPage|null
     */
    public function findPublishedById(int $id): ?AboutPage
    {
        return AboutPage::published()
            ->active()
            ->find($id);
    }

    /**
     * Get the first published about page.
     *
     * @return AboutPage|null
     */
    public function getFirstPublished(): ?AboutPage
    {
        return AboutPage::published()
            ->active()
            ->ordered()
            ->first();
    }

    /**
     * Create a new about page.
     *
     * @param array $data
     * @return AboutPage
     */
    public function create(array $data): AboutPage
    {
        return AboutPage::create($data);
    }

    /**
     * Update an about page.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $aboutPage = $this->findById($id);
        
        if (!$aboutPage) {
            return false;
        }

        return $aboutPage->update($data);
    }

    /**
     * Delete an about page.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $aboutPage = $this->findById($id);
        
        if (!$aboutPage) {
            return false;
        }

        return $aboutPage->delete();
    }

    /**
     * Get total count of about pages.
     *
     * @return int
     */
    public function count(): int
    {
        return AboutPage::count();
    }

    /**
     * Get count of active about pages.
     *
     * @return int
     */
    public function countActive(): int
    {
        return AboutPage::active()->count();
    }

    /**
     * Get count of published about pages.
     *
     * @return int
     */
    public function countPublished(): int
    {
        return AboutPage::published()->active()->count();
    }
}