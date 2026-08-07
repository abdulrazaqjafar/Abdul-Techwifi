<div
    x-data="confirmDialog()"
    x-on:confirm-dialog.window="show($event.detail)"
    x-cloak
>
    <div
        x-show="open"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4"
        role="alertdialog"
        aria-modal="true"
    >
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-secondary-900/70 backdrop-blur-sm"
            @click="cancel()"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md rounded-2xl border border-border bg-surface p-6 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
            @click.stop
        >
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <h3 class="text-center text-lg font-semibold text-secondary-800 dark:text-white" x-text="title"></h3>
            <p class="mt-2 text-center text-sm text-text-muted dark:text-slate-400" x-text="message"></p>
            <div class="mt-6 flex gap-3">
                <x-button variant="outline" class="flex-1" type="button" @click="cancel()" x-text="cancelText"></x-button>
                <x-button variant="danger" class="flex-1" type="button" @click="confirm()" x-text="confirmText"></x-button>
            </div>
        </div>
    </div>
</div>
