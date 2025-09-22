/* Shortcode Copy Functionality */
(function() {
	const copyButtons = document.querySelectorAll('.copy-shortcode-btn');
	if (!copyButtons.length) return;

	copyButtons.forEach(button => {
		button.addEventListener('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			const summary = this.closest('summary');
			const shortcodeText = summary.querySelector('span').textContent;
			
			navigator.clipboard.writeText(shortcodeText).then(() => {
				const originalText = this.textContent;
				this.textContent = aiohm_shortcode_admin.copied_text || 'Copied!';
				this.classList.add('copied');
				
				setTimeout(() => {
					this.textContent = originalText;
					this.classList.remove('copied');
				}, 2000);
			}).catch(err => {
				// Failed to copy shortcode - silently fail in production
			});
		});
	});
})();