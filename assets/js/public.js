/**
 * Kostildemo project.
 *
 * Purpose: performs a frontend logic for job running and admin features.
 */

(function(window, document)
{
    document.addEventListener('DOMContentLoaded', function ()
    {
        const PublicUrl = (function()
        {
            let data = document.documentElement.dataset.publicUrl;
            let url = new URL(data);

            return url.origin + url.pathname;
        })();

        // Initialize Job runner (if required).
        (function()
        {
            const jobKeyElement = document.querySelector('meta[name=job_key]');
            if (!jobKeyElement)
            {
                return;
            }

            fetch(PublicUrl + '?controller=demo&action=cleanup&hash=' + jobKeyElement.content)
                .then(response => response.json())
                .then(data => {
                    if (!data || !data.entries || !data.entries.length)
                    {
                        return;
                    }

                    data.entries.forEach(demo => document.querySelector('[data-demo-id="' + demo + '"]').remove());
                })
                .catch(error => console.error('Error when deleting a demo records:', error));
        })();
    });
})(window, document);
