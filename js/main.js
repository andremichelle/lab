// http://brenna.github.io/csshexagon/
// TODO
// navigation local scrolling
// prev <> next work
// change url, decode url

class Navigation {
    constructor(laboratory, root) {
        this.laboratory = laboratory;
        this.root = root;
        this.workTemplate = root.querySelector("a.work");
        this.workTemplate.remove();
        this.build();
    }

    build() {
        const size = 80;
        const padding = 2;
        let w = 0;
        let h = 0;
        let i = 0;
        let top = padding;
        for (let yi = 0; ; yi++) {
            let even = 1 === (yi & 1);
            let xn = even ? 6 : 5;
            let right = even ? padding : padding + (size + padding) / 2;
            for (let xi = 0; xi < xn; xi++) {
                const work = works[i++];
                const element = this.workTemplate.cloneNode(true);
                element.style.top = top + "px";
                element.style.left = right + "px";
                right += size + padding;
                w = Math.max(w, right);
                element.onclick = () => this.laboratory.loadWork(work);
                const titleSpan = element.querySelector(".title");
                titleSpan.textContent = work.title;
                this.root.appendChild(element);
                if (i === works.length) {
                    break;
                }
            }
            top += size * Math.sin(Math.PI / 3.0) + padding;
            h = Math.max(h, top);
            if (i === works.length) {
                break;
            }
        }
        h = Math.max(h, top + size * Math.sin(Math.PI / 8.0));
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
        this.ruffle = this.rufflePlayer.newest();
        this.player = null;
    }

    show(work) {
        console.log("WorkDisplay::show", work.title);
        const swf = work.swf;
        this.player?.remove();
        this.player = this.ruffle.createPlayer();
        this.player.style.width = swf.width + "px";
        this.player.style.height = swf.height + "px";
        this.player.classList.add("player");
        this.titleLabel.textContent = work.title;
        this.descriptionLabel.innerHTML = work.description;
        this.titleLabel.classList.remove("hidden");
        this.root.classList.remove("hidden");
        this.root.querySelector(".player-wrapper").appendChild(this.player);
        this.player.load({
            url: swf.path,
            parameters: "",
            allowScriptAccess: true
        });
    }

    hide() {
        console.log("WorkDisplay::hide");
        this.titleLabel.classList.add("hidden");
        this.root.classList.add("hidden");
        this.player?.remove();
        this.player = null;
    }
}

class Laboratory {
    constructor() {
        this.navigation = new Navigation(this, document.body.querySelector("nav"));
        this.workDisplay = new WorkDisplay(this, document.body.querySelector("article"));
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