<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Tag Management</h1>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Create New Tag</h2>

                    <form wire:submit.prevent="saveTag" class="space-y-4">
                        <div>
                            <x-input label="Name" wire:model.defer="name" placeholder="Tag Name" />
                            @error('name')
                                <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <x-select label="Super Tag" placeholder="Select a super tag" :options="$tags"
                                option-label="name" option-value="id" wire:model.defer="super_tag" class="w-full" />
                            @error('super_tag')
                                <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <x-color-picker label="Color" wire:model.defer="color" placeholder="Pick a color" />
                            @error('color')
                                <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex justify-end mt-6">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Save Tag
                            </button>
                        </div>
                    </form>

                    @if (session()->has('success'))
                        <div
                            class="mt-3 p-3 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-md">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                        {{ session('success') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Tag Hierarchy</h2>

                    <div class="space-y-4">
                        <x-select label="Root Node" placeholder="Select a root Tag" :options="$rootNodeNames"
                            wire:model.live="selectedRootName" class="w-full" />

                        <div
                            class="border border-gray-200 dark:border-gray-700 rounded-md p-4 bg-gray-50 dark:bg-gray-900 mt-4">
                            <div wire:key="tag-tree-{{ $selectedRootName }}">
                                {{ $this->selectedTagTree }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
