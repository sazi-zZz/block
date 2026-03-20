document.addEventListener('DOMContentLoaded', () => {
    // We poll every 5 seconds for visual post properties (likes, comment counts, content edits)
    // Only fetch for post IDs currently visible in the DOM.
    setInterval(() => {
        const postEls = document.querySelectorAll('[data-post-id]');
        if (postEls.length === 0) return;

        // Extract unique IDs
        const ids = Array.from(postEls).map(el => el.getAttribute('data-post-id'));
        const uniqueIds = [...new Set(ids)];

        fetch('api/sync_live.php?post_ids=' + uniqueIds.join(','))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.posts) {
                    postEls.forEach(el => {
                        const id = el.getAttribute('data-post-id');
                        const postData = data.posts[id];
                        if (!postData) return;

                        // 1. Update Likes Count & Heart Style
                        const likeBtns = el.querySelectorAll('.post-action-btn');
                        // Find the heart button specifically based on fa-heart inside it
                        likeBtns.forEach(btn => {
                            if (btn.querySelector('.fa-heart')) {
                                const countSpan = btn.querySelector('.like-count');
                                if (countSpan) countSpan.textContent = postData.like_count;

                                const icon = btn.querySelector('.fa-heart');
                                if (postData.is_liked) {
                                    icon.classList.remove('fa-regular');
                                    icon.classList.add('fa-solid');
                                    btn.style.color = 'var(--danger)';
                                    btn.classList.add('liked');
                                } else {
                                    icon.classList.remove('fa-solid');
                                    icon.classList.add('fa-regular');
                                    btn.style.color = 'var(--text-muted)';
                                    btn.classList.remove('liked');
                                }
                            }
                        });


                        // 2. Update Comments Count & Check for Main View Comment Sync
                        const commentLinks = el.querySelectorAll('a.post-action-btn');
                        commentLinks.forEach(link => {
                            if (link.querySelector('.fa-comment')) {
                                const span = link.querySelector('span:not(.fa-comment)');
                                if (span) {
                                    const oldCount = parseInt(span.textContent);
                                    span.textContent = postData.comment_count;

                                    if (el.classList.contains('main-post-view') && oldCount !== parseInt(postData.comment_count)) {
                                        // Count changed, fetch fresh comments HTML
                                        fetch(`/api/render_comments.php?post_id=${id}`)
                                            .then(r => r.text())
                                            .then(html => {
                                                const clist = document.querySelector('.comments-list');
                                                if (clist) clist.innerHTML = html;
                                            })
                                            .catch(e => console.error("Comment Sync error:", e));
                                    }
                                }
                            }
                        });

                        // 3. Update Text Content inside feed nodes
                        const titleEl = el.querySelector('h4');
                        if (titleEl && !el.classList.contains('main-post-view')) titleEl.textContent = postData.title;

                        const pEl = el.querySelector('p.text-muted:not(.main-post-view p)');
                        if (pEl && !el.classList.contains('main-post-view')) pEl.innerHTML = postData.content;

                        // If it's the main post view
                        if (el.classList.contains('main-post-view')) {
                            const mainTitle = el.querySelector('h2');
                            if (mainTitle) mainTitle.textContent = postData.title;
                        }
                    });
                }
            })
            .catch(e => console.error("Sync error:", e));
    }, 5000);
});
