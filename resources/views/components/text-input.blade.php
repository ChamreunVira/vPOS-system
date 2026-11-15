@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'px-3 py-1.5 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
