class Navigation {
    constructor(laboratory, root) {
        this.laboratory = laboratory;
        this.root = root;
        this.workTemplate = root.querySelector(".work");
        this.workTemplate.remove();
        this.build();
    }

    build() {
        for (let i = 0; i < works.length; i++) {
            const work = works[i];
            const element = this.workTemplate.cloneNode(true);
            element.onclick = () => this.laboratory.loadWork(work);
            const titleSpan = element.querySelector(".title");
            titleSpan.textContent = work.title;
            this.root.appendChild(element);
        }
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
        this.titleLabel.classList.remove("hidden");
        this.closeButton.classList.remove("hidden");
        this.root.appendChild(this.player);
        this.player.load({
            url: swf.path,
            parameters: "",
            allowScriptAccess: true
        });
    }

    hide() {
        console.log("WorkDisplay::hide");
        this.titleLabel.classList.add("hidden");
        this.closeButton.classList.add("hidden");
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