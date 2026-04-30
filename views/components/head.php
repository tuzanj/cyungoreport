<?php
// views/components/head.php
// Usage: include this at the top of every page
// Variables: $pageTitle (string)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? APP_NAME) ?> — <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary:   { DEFAULT: '#4F46E5', dark: '#3730A3', light: '#818CF8' },
                        secondary: { DEFAULT: '#0EA5E9', dark: '#0284C7' },
                        success:   '#10B981',
                        warning:   '#F59E0B',
                        danger:    '#EF4444',
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background-color: rgba(79,70,229,0.15); color: #4F46E5; border-left: 3px solid #4F46E5; }
        .sidebar-link { border-left: 3px solid transparent; }
        .card-hover { transition: transform .2s, box-shadow .2s; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,.1); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .badge { display:inline-flex; align-items:center; padding: 2px 8px; border-radius:9999px; font-size:.75rem; font-weight:600; }
        @media (max-width: 640px) {
            table { font-size: 0.8rem; }
            table th, table td { padding: 0.5rem 0.75rem !important; }
        }
    </style>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">
