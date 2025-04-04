<div>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-6">Tag Management</h1>

            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Update Tag: <b>{{ $name }}</b></h2>

                <form wire:submit.prevent="updateTag" class="space-y-4">
                    <div>
                        <x-input label="Name" wire:model="name" placeholder="Tag Name" />
                    </div>

                    <div>
                        <x-select label="Super Tag" placeholder="Select a super tag" :options="$tags"
                            option-label="name" option-value="id" wire:model.defer="super_tag" class="w-full" />
                    </div>

                    <div>
                        <x-color-picker label="Color" wire:model.defer="color" placeholder="Pick a color" />
                    </div>

                    <div class="flex justify-between mt-6">
                        <x-button onclick="history.back();" primary label="Back" icon="arrow-left" />
                        <x-button type="submit" primary label="Update Tag" icon="tag" />
                    </div>
                </form>

                @if (session()->has('update_success'))
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
                                    {{ session('update_success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif


                <form wire:submit.prevent="deleteTag" class="space-y-4">
                    <div class="flex justify-end mt-6">
                        <x-button type="submit" negative label="Delete Tag" icon="trash" />
                    </div>
                </form>

                @if (session()->has('delete_success'))
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
                                    {{ session('delete_success') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                @if (session()->has('delete_error'))
                    <div
                        class="mt-3 p-3 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800 dark:text-red-200">
                                    {{ session('delete_error') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
