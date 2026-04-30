<?php
// views/components/flash.php
$flash = getFlash();
if ($flash):
    $bgMap = [
        'success' => 'bg-green-50 border-green-300 text-green-800',
        'danger'  => 'bg-red-50 border-red-300 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-300 text-yellow-800',
        'info'    => 'bg-blue-50 border-blue-300 text-blue-800',
    ];
    $iconMap = [
        'success' => 'fa-circle-check text-green-500',
        'danger'  => 'fa-circle-xmark text-red-500',
        'warning' => 'fa-triangle-exclamation text-yellow-500',
        'info'    => 'fa-circle-info text-blue-500',
    ];
    $bg   = $bgMap[$flash['type']] ?? $bgMap['info'];
    $icon = $iconMap[$flash['type']] ?? $iconMap['info'];
?>
<div class="mb-4 flex items-start gap-3 px-4 py-3 rounded-lg border <?= $bg ?>" x-data="{show:true}" x-show="show" x-transition>
    <i class="fa-solid <?= $icon ?> mt-0.5 flex-shrink-0"></i>
    <div class="flex-1 text-sm"><?= $flash['message'] ?></div>
    <button @click="show=false" class="text-current opacity-50 hover:opacity-100 ml-2"><i class="fa-solid fa-xmark"></i></button>
</div>
<?php endif; ?>
