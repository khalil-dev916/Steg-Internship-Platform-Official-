window.addEventListener('DOMContentLoaded', () => {
	// Demo numbers
	const totals = { total: 12, completed: 5, reports: 27 };
	const el = (id, v) => { const n = document.getElementById(id); if (n) n.textContent = String(v); };
	el('stat-total', totals.total);
	el('stat-completed', totals.completed);
	el('stat-reports', totals.reports);

	// Demo chart
	const ctx = document.getElementById('deptChart');
	if (ctx && window.Chart) {
		new Chart(ctx, {
			type: 'bar',
			data: {
				labels: ['Informatique', 'Finance', 'RH', 'Production'],
				datasets: [{ label: 'Stagiaires', data: [5, 3, 2, 2], backgroundColor: '#1976d233', borderColor: '#1976d2', borderWidth: 1 }]
			},
			options: { responsive: true, scales: { y: { beginAtZero: true } } }
		});
	}
});
