@props(['padding' => 'p-6'])

<div {{ $attributes->merge(['class' => "animate-enter rounded-3xl border border-white/70 bg-white $padding shadow-[0_18px_45px_-28px_rgb(15_23_42/0.45)] ring-1 ring-slate-900/5 transition-shadow duration-300 hover:shadow-[0_22px_50px_-28px_rgb(15_23_42/0.5)] dark:border-white/5 dark:bg-slate-900 dark:ring-white/10"]) }}>
    {{ $slot }}
</div>
