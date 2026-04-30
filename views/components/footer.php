    </div><!-- /p-6 -->
</main>
<script>
// AJAX helper
async function apiPost(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    });
    return res.json();
}

// Auto-dismiss alerts after 5s
document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('[data-auto-dismiss]');
    alerts.forEach(a => setTimeout(() => a.style.opacity = '0', 5000));
});
</script>
</body>
</html>
