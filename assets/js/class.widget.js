class WidgetZabbixStatusList extends CWidget {

    setContents(response) {
        super.setContents(response);
        const svgs = this._body.querySelectorAll('.sparkline-svg');
        svgs.forEach(svg => {
            const crosshair = svg.querySelector('.sparkline-crosshair');
            const clocks = JSON.parse(svg.getAttribute('data-clocks'));
            const values = JSON.parse(svg.getAttribute('data-values'));
            const step = parseFloat(svg.getAttribute('data-step'));

            svg.onmousemove = (e) => {
                const rect = svg.getBoundingClientRect();
                const mouseX = e.clientX - rect.left;
                const ratio = mouseX / rect.width;
                let index = Math.round(ratio * (clocks.length - 1));
                index = Math.max(0, Math.min(index, clocks.length - 1));
                
                const viewBoxWidth = 250; 
                const xInViewBox = (index / (clocks.length - 1)) * viewBoxWidth;  
                const x = index * step;
                crosshair.setAttribute('x1', xInViewBox);
                crosshair.setAttribute('x2', xInViewBox);
                crosshair.style.display = 'block';
                
                const date = new Date(clocks[index] * 1000);
                const timeStr = date.toLocaleTimeString([], {
                    hour: '2-digit', minute: '2-digit', second: '2-digit', day: '2-digit', month: '2-digit'
                });

                const isBinary = svg.getAttribute('data-is-binary') === '1';
                const units = svg.getAttribute('data-units') || '';

                let valueText = '';
                if (isBinary) {
                    valueText = (parseFloat(values[index]) > 0) ? 'UP' : 'DOWN';
                } else {
                    const val = parseFloat(values[index]);
                    const formattedVal = (val % 1 === 0) ? val : val.toFixed(2);
                    valueText = `${formattedVal} ${units}`;
                }

                const fullText = `${timeStr} | ${valueText}`;
                this._showTooltip(e, fullText);
            };

            svg.onmouseleave = () => {
                crosshair.style.display = 'none';
                this._hideTooltip();
            };
        });
    }

    _showTooltip(e, text) {
        let tooltip = document.getElementById('sparkline-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.id = 'sparkline-tooltip';
            tooltip.style = `
                position: fixed; 
                background: rgba(0, 0, 0, 0.85); 
                color: #ffffff; 
                padding: 5px 10px; 
                border-radius: 3px; 
                font-size: 11px; 
                z-index: 20000; 
                pointer-events: none; 
                border: 1px solid #666;
                white-space: nowrap;
                font-family: Arial, sans-serif;
            `;
            document.body.appendChild(tooltip);
        }
        tooltip.innerText = text;
        tooltip.style.display = 'block';

        const offset = 15; 
        const tooltipWidth = tooltip.offsetWidth; 
        const windowWidth = document.documentElement.clientWidth; 

        let leftPos = e.clientX + offset;
        const topPos = e.clientY - 30;

        if (leftPos + tooltipWidth > windowWidth) {
            leftPos = e.clientX - tooltipWidth - offset;
        }

        tooltip.style.left = leftPos + 'px';
        tooltip.style.top = topPos + 'px';
    }

    _hideTooltip() {
        const tooltip = document.getElementById('sparkline-tooltip');
        if (tooltip) tooltip.style.display = 'none';
    }
}
