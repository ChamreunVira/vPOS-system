export default function salesChart(series = []) {
    return {
        series: Array.isArray(series) ? series : [],

        width: 0,
        containerHeight: 0,

        hoverIndex: null,

        tooltip: {
            x: 0,
            y: 0,
            visible: false,
        },

        resizeObserver: null,

        // Layout constants
        paddingTop: 16,
        paddingBottom: 36,
        paddingLeft: 52,
        paddingRight: 16,

        // Chart dimensions
        get chartWidth() {
            return Math.max(
                0,
                this.width - this.paddingLeft - this.paddingRight,
            );
        },

        get chartHeight() {
            return Math.max(
                0,
                this.containerHeight - this.paddingTop - this.paddingBottom,
            );
        },

        // Maximum data value
        get maxValue() {
            const values = this.series.flatMap((item) => [
                Number(item.value) || 0,
                Number(item.previous) || 0,
            ]);

            return Math.max(1, ...values);
        },

        // Rounded maximum value for Y-axis
        get niceCeil() {
            const max = this.maxValue;
            const magnitude = Math.pow(10, Math.floor(Math.log10(max)));

            return Math.ceil(max / magnitude) * magnitude;
        },

        // Y-axis ticks
        get yTicks() {
            const count = 4;
            const step = this.niceCeil / count;

            return Array.from(
                { length: count + 1 },
                (_, index) => index * step,
            ).reverse();
        },

        // X-axis position
        xPos(index) {
            if (this.series.length <= 1) {
                return this.paddingLeft + this.chartWidth / 2;
            }

            return (
                this.paddingLeft +
                (index / (this.series.length - 1)) * this.chartWidth
            );
        },

        // Y-axis position
        yPos(value) {
            const normalizedValue = Math.max(0, Number(value) || 0);

            return (
                this.paddingTop +
                this.chartHeight -
                (normalizedValue / this.niceCeil) * this.chartHeight
            );
        },

        // Build straight line path
        buildPath(key) {
            if (!this.series.length) return "";

            return this.series
                .map((item, index) => {
                    const command = index === 0 ? "M" : "L";

                    return `${command}${this.xPos(index).toFixed(1)},${this.yPos(item[key] ?? 0).toFixed(1)}`;
                })
                .join(" ");
        },

        // Build smooth cubic curve
        buildSmoothPath(key) {
            if (this.series.length < 2) {
                return this.buildPath(key);
            }

            const points = this.series.map((item, index) => ({
                x: this.xPos(index),
                y: this.yPos(item[key] ?? 0),
            }));

            let path = `M${points[0].x.toFixed(1)},${points[0].y.toFixed(1)}`;

            for (let index = 1; index < points.length; index++) {
                const previous = points[index - 1];
                const current = points[index];

                const controlX = (previous.x + current.x) / 2;

                path +=
                    ` C${controlX.toFixed(1)},${previous.y.toFixed(1)}` +
                    ` ${controlX.toFixed(1)},${current.y.toFixed(1)}` +
                    ` ${current.x.toFixed(1)},${current.y.toFixed(1)}`;
            }

            return path;
        },

        // Build smooth area path
        buildSmoothAreaPath(key) {
            if (!this.series.length) return "";

            const bottom = (this.paddingTop + this.chartHeight).toFixed(1);
            const firstX = this.xPos(0).toFixed(1);
            const lastX = this.xPos(this.series.length - 1).toFixed(1);

            return (
                `${this.buildSmoothPath(key)} ` +
                `L${lastX},${bottom} ` +
                `L${firstX},${bottom} Z`
            );
        },

        // Format money
        formatMoney(value) {
            const amount = Number(value) || 0;

            if (amount >= 1000) {
                return "$" + (amount / 1000).toFixed(1) + "k";
            }

            return "$" + amount.toFixed(Number.isInteger(amount) ? 0 : 2);
        },

        // Handle mouse movement
        onMouseMove(event) {
            if (!this.series.length || this.chartWidth <= 0) return;

            const rect = event.currentTarget.getBoundingClientRect();

            const mouseX = event.clientX - rect.left - this.paddingLeft;

            let index = 0;

            if (this.series.length > 1) {
                const step = this.chartWidth / (this.series.length - 1);

                index = Math.round(mouseX / step);
            }

            index = Math.min(Math.max(0, index), this.series.length - 1);

            this.hoverIndex = index;

            const centerX = this.xPos(index);
            const centerY = this.yPos(this.series[index]?.value ?? 0);

            const tooltipWidth = 150;
            const tooltipHeight = 90;

            this.tooltip = {
                x: Math.max(
                    0,
                    Math.min(
                        centerX - tooltipWidth / 2,
                        this.width - tooltipWidth,
                    ),
                ),

                y: Math.max(
                    0,
                    Math.min(
                        centerY - tooltipHeight - 12,
                        this.containerHeight - tooltipHeight,
                    ),
                ),

                visible: true,
            };
        },

        // Handle mouse leave
        onMouseLeave() {
            this.hoverIndex = null;

            this.tooltip.visible = false;
        },

        // Initialize responsive chart
        init() {
            const element = this.$el;

            this.resizeObserver = new ResizeObserver(([entry]) => {
                this.width = entry.contentRect.width;
                this.containerHeight = entry.contentRect.height;
            });

            this.resizeObserver.observe(element);

            this.$cleanup(() => {
                this.resizeObserver?.disconnect();
            });
        },
    };
}
