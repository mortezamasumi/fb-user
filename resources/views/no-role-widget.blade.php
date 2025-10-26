<x-filament-widgets::widget>
    <x-filament::section class="text-lg" icon="heroicon-o-exclamation-triangle" icon-color="warning" icon-size="2xl">
        <div>
            @lang('fb-user::fb-user.no-role-widget.text', ['hours' => __digit(config('fbase.setup.remove_unattend_user_hours', 48))])
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
