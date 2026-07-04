!(function () {
    "use strict";
    var t, a, e;
    sessionStorage.getItem("defaultAttribute") &&
        ((t = document.documentElement.attributes),
        (a = {}),
        Object.entries(t).forEach(function (t) {
            var e;
            t[1] &&
                t[1].nodeName &&
                "undefined" != t[1].nodeName &&
                ((e = t[1].nodeName), (a[e] = t[1].nodeValue));
        }),
        sessionStorage.getItem("defaultAttribute") !== JSON.stringify(a)
            ? (sessionStorage.clear(), window.location.reload())
            : (((e = {})["data-layout"] = "vertical"),
              (e["data-sidebar-size"] = "lg"),
              (e["data-bs-theme"] = sessionStorage.getItem("data-bs-theme")),
              (e["data-layout-width"] = "fluid"),
              (e["data-sidebar"] = "dark"),
              (e["data-sidebar-image"] =
                  sessionStorage.getItem("data-sidebar-image")),
              (e["data-layout-direction"] = sessionStorage.getItem(
                  "data-layout-direction"
              )),
              (e["data-layout-position"] = "fixed"),
              (e["data-layout-style"] = "detached"),
              (e["data-topbar"] = "light"),
              (e["data-preloader"] = "disable"),
              (e["data-body-image"] = "none"),
              Object.keys(e).forEach(function (t) {
                  e[t] && document.documentElement.setAttribute(t, e[t]);
              })));
})();
