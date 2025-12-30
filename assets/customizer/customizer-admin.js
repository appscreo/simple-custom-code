class SccPreloaderOverlay {
  constructor() {
    this.injectStyles();
    this.injectHTML();
  }

  injectStyles() {
    const style = document.createElement('style');
    style.textContent = `
      #scc-preloader-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.6);
        z-index: 999999999;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
      }

      #scc-preloader-spinner {
        border: 6px solid #f3f3f3;
        border-top: 6px solid #3498db;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: scc-spin 1s linear infinite;
        margin-bottom: 20px;
      }

      @keyframes scc-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }

      #scc-preloader-message {
        color: white;
        font-size: 1.4rem;
        font-family: sans-serif;
        text-align: center;
        background: rgba(0, 0, 0, 0.8);
        padding: 0.8em 1.5em;
        border-radius: 8px;
        box-shadow: 0 0 10px #000;
        max-width: 80%;
      }
    `;
    document.head.appendChild(style);
  }

  injectHTML() {
    const overlay = document.createElement('div');
    overlay.id = 'scc-preloader-overlay';
    overlay.innerHTML = `
      <div id="scc-preloader-spinner"></div>
      <div id="scc-preloader-message">Loading...</div>
    `;
    document.body.appendChild(overlay);
  }

  show(message = 'Loading...') {
    const overlay = document.getElementById('scc-preloader-overlay');
    const msg = document.getElementById('scc-preloader-message');
    if (overlay && msg) {
      msg.textContent = message;
      overlay.style.display = 'flex';
    }
  }

  hide() {
    const overlay = document.getElementById('scc-preloader-overlay');
    if (overlay) {
      overlay.style.display = 'none';
    }
  }
}



const SCCCustomizer = {
    openOverlay: function () {
        if (document.getElementById('scc-overlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'scc-overlay';
        overlay.innerHTML = `
            <div class="scc-modal">
                <h2>Select a CSS Snippet</h2>
                <div id="scc-list">Loading...</div>
                <button onclick="SCCCustomizer.closeOverlay()">Close</button>
            </div>
        `;
        document.body.appendChild(overlay);
        this.fetchPosts();
    },

    closeOverlay: function () {
        const overlay = document.getElementById('scc-overlay');
        if (overlay) overlay.remove();
    },

    fetchPosts: function () {
        fetch(SCCCustomizer_Menu_Settings.rest_url, {
            headers: {
                'X-WP-Nonce': SCCCustomizer_Menu_Settings.nonce
            }
        })
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('scc-list');
            if (!data.length) {
                list.innerHTML = '<p>No CSS snippets found. Please create one first.</p>';
                return;
            }

            list.innerHTML = '<ul>' + data.map(post => `
                <li>
                    <a href="#" onclick="SCCCustomizer.selectPost(${post.id}); return false;">
                        ${post.title} (ID: ${post.id}) ${post.active == '1' ? '<span class="status status-active">Active</span>' : '<span class="status status-inactive">Inactive</span>'}
                    </a>
                </li>
            `).join('') + '</ul>';
        })
        .catch(err => {
            document.getElementById('scc-list').innerHTML = '<p>Error loading posts.</p>';
            console.error(err);
        });
    },

    selectPost: function (id) {
        const url = new URL(window.location.href);
        url.searchParams.set('scc_customizer', 'true');
        url.searchParams.set('scc', id);
        window.location.href = url.toString();
    }
};