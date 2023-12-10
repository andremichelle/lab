// TODO
// change url, decode url
// Revive Jarcase Instant Sampler if possible

class SVG {
    static create(width, height) {
        const svg = document.createElementNS(SVG.NS, "svg");
        svg.setAttribute("version", "1.1");
        svg.setAttribute("viewBox", "0 0 " + width + " " + height);
        svg.style.width = width + "px";
        svg.style.height = height + "px";
        svg.style.userSelect = "none";
        svg.style.outline = "none";
        return svg;
    }

    static createElement(name) {
        return document.createElementNS(SVG.NS, name);
    }
}

SVG.NS = "http://www.w3.org/2000/svg";

class Hexagon {
    constructor(title, radius) {
        this.title = title;
        this.radius = radius;
        this.width = Math.ceil(Math.sqrt(3.0) * radius / 2.0) << 1;
        this.height = Math.ceil(2.0 * radius) + 2.0;
    }

    corner(index) {
        const angle_deg = 60.0 * index - 30.0;
        const angle_rad = Math.PI / 180.0 * angle_deg;
        return {
            x: this.width / 2.0 + this.radius * Math.cos(angle_rad),
            y: this.height / 2.0 + this.radius * Math.sin(angle_rad)
        };
    }

    htmlElement() {
        const root = document.createElement("DIV");
        root.classList.add("hex");
        const svg = SVG.create(this.width, this.height);
        const polygon = SVG.createElement("polygon");
        const points = [];
        for (let i = 0; i < 6; i++) {
            points[i] = this.corner(i);
        }
        polygon.setAttribute("points", points
            .map(({x, y}) => [x.toFixed(3), y.toFixed(3)].join(","))
            .join(" "));
        svg.appendChild(polygon);
        root.appendChild(svg);
        const span = document.createElement("SPAN");
        span.textContent = this.title;
        root.appendChild(span);
        return root;
    }
}

class Navigation {
    constructor(laboratory, root) {
        this.laboratory = laboratory;
        this.root = root;
        this.workTemplate = root.querySelector("a.work");
        this.workTemplate.remove();
        this.build();
    }

    build() {
        const size = 84;
        const radius = 44;
        let w = 0;
        let h = 0;
        let i = 0;
        let top = 0;
        for (let yi = 0; ; yi++) {
            const even = 1 === (yi & 1);
            const xn = even ? 9 : 8;
            let right = even ? 0 : size / 2;
            for (let xi = 0; xi < xn; xi++) {
                const work = works[i];
                work.index = i++;
                const element = new Hexagon(work.title, radius).htmlElement();
                element.style.top = top + "px";
                element.style.left = right + "px";
                if (work.ruffle) {
                    element.classList.add("broken");
                }
                right += size;
                w = Math.max(w, right);
                element.onclick = (event) => {
                    event.preventDefault()
                    event.stopPropagation()
                    this.laboratory.loadWork(work)
                };
                this.root.appendChild(element);
                if (i === works.length) {
                    break;
                }
            }
            top += size * Math.sin(Math.PI / 3.0);
            h = Math.max(h, top);
            if (i === works.length) {
                break;
            }
        }
        h = Math.max(h, top + size * Math.sin(Math.PI / 16.0));
        this.root.style.width = w + "px";
        this.root.style.height = h + "px";
    }

    show() {
        console.log("Navigation::show");
        this.root.classList.remove("inactive");
    }

    hide() {
        console.log("Navigation::hide");
        this.root.classList.add("inactive");
    }
}

class WorkDisplay {
    constructor(laboratory, root) {
        this.laboratory = laboratory;
        this.root = root;
        this.titleLabel = root.querySelector(".title");
        this.descriptionLabel = root.querySelector(".description");
        this.prevButton = root.querySelector(".prev");
        this.nextButton = root.querySelector(".next");
        this.closeButton = root.querySelector(".close");
        this.rufflePlayer = window.RufflePlayer;
        this.rufflePlayer.config = {
            "publicPath": undefined,
            "polyfills": true,
            "autoplay": "auto",
            "unmuteOverlay": "visible",
            "backgroundColor": null,
            "letterbox": "fullscreen",
            "warnOnUnsupportedContent": true,
            "contextMenu": false,
            "upgradeToHttps": window.location.protocol === "https:",
            "maxExecutionDuration": {"secs": 15, "nanos": 0},
            "logLevel": "error",
        };
        this.closeButton.onclick = () => this.laboratory.hideWork();
        this.prevButton.onclick = () => this.prev();
        this.nextButton.onclick = () => this.next();
        this.ruffle = this.rufflePlayer.newest();
        this.player = null;
        this.index = -1;
    }

    show(work) {
        this.hide();
        console.log("WorkDisplay::show", work.title);
        const swf = work.swf;
        this.player = this.ruffle.createPlayer();
        this.player.style.width = swf.width + "px";
        this.player.style.height = swf.height + "px";
        this.player.classList.add("player");
        this.titleLabel.textContent = work.title;
        if (work.ruffle) {
            this.descriptionLabel.classList.add("broken");
            this.descriptionLabel.innerHTML = work.ruffle;
        } else {
            this.descriptionLabel.classList.remove("broken");
            this.descriptionLabel.innerHTML = work.description;
        }
        this.titleLabel.classList.remove("hidden");
        this.root.classList.remove("hidden");
        this.root.querySelector(".player-wrapper").appendChild(this.player);
        this.index = work.index;
        this.player.load({
            url: swf.path, parameters: "", allowScriptAccess: true
        });
    }

    prev() {
        const newIndex = (this.index + works.length - 1) % works.length;
        this.show(works[newIndex]);
    }

    next() {
        const newIndex = (this.index + 1) % works.length;
        this.show(works[newIndex]);
    }

    hide() {
        if (null === this.player) {
            return;
        }
        console.log("WorkDisplay::hide");
        this.titleLabel.classList.add("hidden");
        this.root.classList.add("hidden");
        this.player.remove();
        this.player = null;
        this.index = -1;
    }

    isVisible() {
        return this.player !== null
    }
}

class Laboratory {
    constructor() {
        this.navigation = new Navigation(this, document.body.querySelector("nav"));
        this.workDisplay = new WorkDisplay(this, document.body.querySelector("article"));

        window.addEventListener("click", event => {
            if (!this.workDisplay.isVisible()) {
                return
            }
            const element = this.workDisplay.root.querySelector(".player-wrapper");
            if (!element.contains(event.target)) {
                this.hideWork()
            }
        })
    }

    loadWork(work) {
        this.navigation.hide();
        this.workDisplay.show(work);
    }

    hideWork() {
        this.navigation.show();
        this.workDisplay.hide();
    }
}

window.addEventListener("load", () => new Laboratory());