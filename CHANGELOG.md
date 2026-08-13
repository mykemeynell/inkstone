# Changelog

## [1.4.0](https://github.com/mykemeynell/inkstone/compare/v1.3.0...v1.4.0) (2026-08-13)


### Features

* extensions ([#11](https://github.com/mykemeynell/inkstone/issues/11)) ([481c2ed](https://github.com/mykemeynell/inkstone/commit/481c2ed4bd55c40977871eedb247481198abccea))

## [1.3.0](https://github.com/mykemeynell/inkstone/compare/v1.2.0...v1.3.0) (2026-07-22)


### Features

* add OpenAPI specification parsing to the documentation pipeline ([bd7b49f](https://github.com/mykemeynell/inkstone/commit/bd7b49f4841df2fcef016407d8106efa38551601))
* **facade:** add docs built status check ([cb34e39](https://github.com/mykemeynell/inkstone/commit/cb34e395d78876efbc50ce47c7c9d21a1ba22edf))
* openapi specification parsing ([8c665f2](https://github.com/mykemeynell/inkstone/commit/8c665f2c2ed93b492622c35de83bc67281f6b30d))
* **routes:** serve generated docs from Laravel routes ([6e61180](https://github.com/mykemeynell/inkstone/commit/6e61180e7de7837e3d4997281ce66c350aa0ae69))


### Bug Fixes

* **api:** use maintained OpenAPI parser fork ([c216e6d](https://github.com/mykemeynell/inkstone/commit/c216e6dc3d08db9282c875304a321cdaea20bbea))
* **routes:** cover route-served docs asset contracts ([44dff41](https://github.com/mykemeynell/inkstone/commit/44dff410632b0cab249c6613d69fa01e184805ad))
* **routes:** rewrite generated asset URLs when mounted ([f8ac0a0](https://github.com/mykemeynell/inkstone/commit/f8ac0a078e7d788fb1f94905c50ba1c63fdd2509))
* **routes:** rewrite served search result urls ([80c56bf](https://github.com/mykemeynell/inkstone/commit/80c56bfaf04e75e1b7f602e3831712c585dd68b7))
* **routes:** set MIME types for served assets ([7ebefa9](https://github.com/mykemeynell/inkstone/commit/7ebefa961d7aed47dbd0bb8ed4dc5101d923d463))


### Miscellaneous Chores

* remove Laravel 11 from testing requirements. ([1f5f129](https://github.com/mykemeynell/inkstone/commit/1f5f129625597203e0330438060fb32c4056f02a))

## [1.2.0](https://github.com/mykemeynell/inkstone/compare/v1.1.0...v1.2.0) (2026-06-01)


### Features

* add BaseUrlLinkTransformer to prepend base_url to root-relative links ([38f53dc](https://github.com/mykemeynell/inkstone/commit/38f53dc8eac7a5664e6e3d8661dcb114517568d8))
* add footer.repository config for repository link ([1c27172](https://github.com/mykemeynell/inkstone/commit/1c271728008cf5aa501fcdb081a67448e1312b18))
* add LinkChecker service and BrokenLinkReport DTO ([045d2ea](https://github.com/mykemeynell/inkstone/commit/045d2eacf9b910fa0803a2697d7b439dbf168653))
* add repository icon partial ([eaa93de](https://github.com/mykemeynell/inkstone/commit/eaa93de6a18492ffe1d4be635ba83f864e125909))
* drive footer repository link from config ([61a9741](https://github.com/mykemeynell/inkstone/commit/61a9741f7ad5762772214ca2ec667c7060037793))
* integrate LinkChecker into build pipeline ([cb70090](https://github.com/mykemeynell/inkstone/commit/cb70090c87a292c3bfecdea3b19b8abdef4cb673))
* update footer link to docs site and add repository icon CSS ([391619d](https://github.com/mykemeynell/inkstone/commit/391619de44b002eaa4d750b2ab20dc42d40a2c11))


### Bug Fixes

* **css:** added bg color to code copy button to improve visibility when overlapping code ([2d6dd71](https://github.com/mykemeynell/inkstone/commit/2d6dd711324b8c905a955d33d866279d89ce5658))
* linting fixes — add ext-dom dependency, remove unused DOMXPath import ([ff0133c](https://github.com/mykemeynell/inkstone/commit/ff0133c6e08f5c6d7e338d6bf2a37b6d55357ea3))


### Miscellaneous Chores

* updated stub logo to Inkstone logo ([a6aaaf7](https://github.com/mykemeynell/inkstone/commit/a6aaaf780068b4cd368a068533b36f77a402bae6))

## [1.1.0](https://github.com/mykemeynell/inkstone/compare/v1.0.0...v1.1.0) (2026-05-30)


### Features

* add theme layout support and code block improvements ([9325914](https://github.com/mykemeynell/inkstone/commit/9325914e81daeabd672175f324f67ad88fca2175))
* major v1.1 feature accumulation ([2aad367](https://github.com/mykemeynell/inkstone/commit/2aad3671ef2142604e5fef00c235b1d471170c63))


### Bug Fixes

* correct env var names in workflow (DOCS_ -&gt; INKSTONE_) ([fd86f86](https://github.com/mykemeynell/inkstone/commit/fd86f861c0504726946e2ab5aaa79425f4bf1d6f))
* fixed code block font size on small viewports ([9325914](https://github.com/mykemeynell/inkstone/commit/9325914e81daeabd672175f324f67ad88fca2175))
* navigation menu on mobile devices scrollable ([9325914](https://github.com/mykemeynell/inkstone/commit/9325914e81daeabd672175f324f67ad88fca2175))
* remove unused themeFromString method ([354b5b8](https://github.com/mykemeynell/inkstone/commit/354b5b8098becea0f6b5dda22f2cbb30a6231682))


### Miscellaneous Chores

* **tests:** include Laravel Dusk in composer.json for better testing support ([f9ac012](https://github.com/mykemeynell/inkstone/commit/f9ac0122c4416c2fbc0f8b07b7556e8ea165c313))

## 1.0.0 (2026-05-29)


### Miscellaneous Chores

* initial release v1.0.0 ([84937d0](https://github.com/mykemeynell/inkstone/commit/84937d04f329463d22380ecf78d89f293f3fadb5))
