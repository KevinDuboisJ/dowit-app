<!-- This component's only purpose is to remove the prefix from the icon name provided by the icon-picker-->

<div x-data="{ hasOption: $el.parentElement?.getAttribute('role') === 'option' }" :class="{ 'w-4 h-4': !hasOption }"
class="flex flex-col items-center justify-center icon-container ">
	<div class="relative w-full !h-16 flex flex-col items-center justify-center py-2">
		<div class="relative w-12 h-12 grow-1 shrink-0 gap-1" :class="{ 'w-9 h-9': !hasOption }">
			<x-filament::icon
				icon="{{$icon}}"
				class="w-full h-full absolute"
				x-ref="icon"
			/>
			{{-- Ugly fix for choices.js not registering clicks on SVGs. --}}
			<div class="w-full h-full absolute z-10"></div>
		</div>
		<small x-show="hasOption" class="w-full text-center grow-0 shrink-0 h-4 truncate">{{ Str::of($icon)->contains('-') ? Str::of($icon)->after('-') : $icon }}</small>
	</div>
</div>