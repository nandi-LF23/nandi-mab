{
    "version": 8,
    "name": "Satellite Streets",
    "metadata": {
        "mapbox:type": "default",
        "mapbox:origin": "satellite-streets-v11",
        "mapbox:sdk-support": {
            "android": "10.6.0",
            "ios": "10.6.0",
            "js": "2.9.0"
        },
        "mapbox:autocomposite": true,
        "mapbox:groups": {
            "Transit, transit-labels": {
                "name": "Transit, transit-labels",
                "collapsed": true
            },
            "Administrative boundaries, admin": {
                "name": "Administrative boundaries, admin",
                "collapsed": true
            },
            "Road network, bridges": {
                "name": "Road network, bridges",
                "collapsed": true
            },
            "Road network, tunnels": {
                "name": "Road network, tunnels",
                "collapsed": true
            },
            "Road network, road-labels": {
                "name": "Road network, road-labels",
                "collapsed": true
            },
            "Natural features, natural-labels": {
                "name": "Natural features, natural-labels",
                "collapsed": true
            },
            "Road network, surface": {
                "name": "Road network, surface",
                "collapsed": true
            },
            "Walking, cycling, etc., barriers-bridges": {
                "name": "Walking, cycling, etc., barriers-bridges",
                "collapsed": true
            },
            "Place labels, place-labels": {
                "name": "Place labels, place-labels",
                "collapsed": true
            },
            "Transit, elevated": {
                "name": "Transit, elevated",
                "collapsed": true
            },
            "Point of interest labels, poi-labels": {
                "name": "Point of interest labels, poi-labels",
                "collapsed": true
            },
            "Walking, cycling, etc., tunnels": {
                "name": "Walking, cycling, etc., tunnels",
                "collapsed": true
            },
            "Road network, tunnels-case": {
                "name": "Road network, tunnels-case",
                "collapsed": true
            },
            "Walking, cycling, etc., surface": {
                "name": "Walking, cycling, etc., surface",
                "collapsed": true
            },
            "Road network, surface-icons": {
                "name": "Road network, surface-icons",
                "collapsed": true
            },
            "Satellite imagery, land": {
                "name": "Satellite imagery, land",
                "collapsed": false
            },
            "Transit, ferry-aerialway-labels": {
                "name": "Transit, ferry-aerialway-labels",
                "collapsed": true
            }
        },
        "mapbox:uiParadigm": "layers",
        "mapbox:decompiler": {
            "id": "cl5grk342004114pvuo5mhar5",
            "componentVersion": "13.0.0",
            "strata": [
                {
                    "id": "satellite-streets-v11",
                    "order": [
                        ["transit", "built"],
                        ["satellite", "land"],
                        "satellite copy",
                        ["road-network", "tunnels-case"],
                        ["walking-cycling", "tunnels"],
                        ["road-network", "tunnels"],
                        ["transit", "ferries"],
                        ["walking-cycling", "surface"],
                        ["road-network", "surface"],
                        ["transit", "surface"],
                        ["road-network", "surface-icons"],
                        ["walking-cycling", "barriers-bridges"],
                        ["road-network", "bridges"],
                        ["transit", "bridges"],
                        ["transit", "elevated"],
                        ["admin-boundaries", "admin"],
                        ["road-network", "road-labels"],
                        ["walking-cycling", "walking-cycling-labels"],
                        ["transit", "ferry-aerialway-labels"],
                        ["natural-features", "natural-labels"],
                        ["point-of-interest-labels", "poi-labels"],
                        ["transit", "transit-labels"],
                        ["place-labels", "place-labels"]
                    ]
                }
            ],
            "components": {
                "satellite": "15.0.0",
                "road-network": "15.0.0",
                "walking-cycling": "15.0.0",
                "admin-boundaries": "15.0.0",
                "natural-features": "15.0.0",
                "point-of-interest-labels": "15.0.0",
                "transit": "15.0.0",
                "place-labels": "15.0.0"
            },
            "propConfig": {
                "satellite": {"satelliteStyle": "Standard"},
                "road-network": {
                    "roadsFont": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                    "colorRoad": "hsla(0, 0%, 90%, 0.5)",
                    "construction": false,
                    "colorRoadLabel": "hsl(0, 0%, 100%)",
                    "fadeOutRoadsOnZoom": true,
                    "colorBase": "hsl(0, 0%, 0%)",
                    "railwayCrossings": false,
                    "colorMotorwayTrunk": "hsla(36, 87%, 68%, 0.7)",
                    "polygonFeatures": false,
                    "minorRoads": false,
                    "turningCircles": false,
                    "colorRoadOutline": "hsla(0, 1%, 10%, 0.5)"
                },
                "walking-cycling": {
                    "roadsFont": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                    "colorRoad": "hsla(0, 0%, 90%, 0.5)",
                    "golfHoleLabelLine": false,
                    "colorGreenspace": "hsl(94, 100%, 14%)",
                    "colorGreenspaceLabel": "hsl(94, 100%, 74%)",
                    "walkingCyclingPisteBackground": false,
                    "colorRoadLabel": "hsl(0, 0%, 100%)",
                    "colorBase": "hsl(0, 0%, 0%)",
                    "poiEtcFont": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                    "pedestrianPolygonFeatures": false,
                    "colorRoadOutline": "hsla(0, 1%, 10%, 0.5)",
                    "labels": false
                },
                "admin-boundaries": {
                    "admin0Width": 1.5,
                    "colorBase": "hsl(0, 0%, 0%)",
                    "colorPlaceLabel": "hsl(0, 0%, 100%)",
                    "colorAdminBoundary": "hsl(0, 0%, 0%)"
                },
                "natural-features": {
                    "poiEtcFont": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                    "colorBase": "hsl(0, 0%, 0%)",
                    "colorWater": "hsl(196, 50%, 50%)",
                    "colorPoi": "hsl(0, 0%, 100%)",
                    "labelHalo": true
                },
                "point-of-interest-labels": {
                    "poiEtcFont": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                    "colorBase": "hsl(0, 0%, 0%)",
                    "colorPoi": "hsl(0, 0%, 100%)",
                    "colorGreenspace": "hsl(94, 100%, 14%)",
                    "colorGreenspaceLabel": "hsl(94, 100%, 74%)",
                    "colorHospital": "hsl(0, 0%, 100%)",
                    "colorSchool": "hsl(0, 0%, 100%)"
                },
                "transit": {
                    "matchLabelAndIcon": false,
                    "roadsFont": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                    "colorRoad": "hsla(0, 0%, 90%, 0.5)",
                    "aeroways": false,
                    "colorAirport": "hsl(0, 0%, 100%)",
                    "colorTransit": "hsl(0, 0%, 100%)",
                    "colorRoadLabel": "hsl(0, 0%, 100%)",
                    "colorBase": "hsl(0, 0%, 0%)",
                    "colorWater": "hsl(196, 50%, 50%)",
                    "railways": false,
                    "ferries": false,
                    "poiEtcFont": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                    "colorRoadOutline": "hsla(0, 1%, 10%, 0.5)"
                },
                "place-labels": {
                    "settlementsMinorFont": [
                        "DIN Pro Medium",
                        "Arial Unicode MS Regular"
                    ],
                    "settlementSubdivisionsFont": [
                        "DIN Pro Medium",
                        "Arial Unicode MS Regular"
                    ],
                    "colorBase": "hsl(0, 0%, 0%)",
                    "colorPlaceLabel": "hsl(0, 0%, 100%)"
                }
            }
        }
    },
    "center": [-117.9206219811403, 33.81032781231458],
    "zoom": 14.123109389983622,
    "bearing": 0,
    "pitch": 0,
    "fog": {
        "range": [1, 10],
        "color": [
            "interpolate",
            ["exponential", 1.2],
            ["zoom"],
            5.5,
            "hsl(240, 12%, 70%)",
            6,
            "hsl(0, 0%, 100%)"
        ],
        "high-color": [
            "interpolate",
            ["exponential", 1.2],
            ["zoom"],
            5.5,
            "hsl(240, 12%, 7%)",
            6,
            "hsl(38, 63%, 84%)"
        ],
        "space-color": [
            "interpolate",
            ["exponential", 1.2],
            ["zoom"],
            5.5,
            "hsl(240, 12%, 9%)",
            6,
            "hsl(199, 61%, 80%)"
        ],
        "horizon-blend": [
            "interpolate",
            ["exponential", 1.2],
            ["zoom"],
            5.5,
            0.008,
            6,
            0.15
        ],
        "star-intensity": [
            "interpolate",
            ["exponential", 1.2],
            ["zoom"],
            5.5,
            0.1,
            6,
            0
        ]
    },
    "sources": {
        "mapbox://mapbox.satellite": {
            "url": "mapbox://mapbox.satellite",
            "type": "raster",
            "tileSize": 256
        },
        "mapbox://davelf.7a4taqz6": {
            "url": "mapbox://davelf.7a4taqz6",
            "type": "raster",
            "tileSize": 256
        },
        "composite": {
            "url": "mapbox://mapbox.mapbox-streets-v8",
            "type": "vector"
        }
    },
    "sprite": "mapbox://sprites/davelf/cl5grk342004114pvuo5mhar5/1nfoztnzmq0bplfkxcttmmxpn",
    "glyphs": "mapbox://fonts/mapbox/{fontstack}/{range}.pbf",
    "projection": {"name": "globe"},
    "layers": [
        {
            "id": "background",
            "type": "background",
            "metadata": {
                "mapbox:featureComponent": "satellite",
                "mapbox:group": "Satellite imagery, land"
            },
            "layout": {},
            "paint": {
                "background-color": "hsl(222, 56%, 4%)",
                "background-opacity": 0.9
            }
        },
        {
            "id": "satellite",
            "type": "raster",
            "metadata": {
                "mapbox:featureComponent": "satellite",
                "mapbox:group": "Satellite imagery, land"
            },
            "source": "mapbox://mapbox.satellite",
            "layout": {},
            "paint": {}
        },
        {
            "id": "satellite copy",
            "type": "raster",
            "source": "mapbox://davelf.7a4taqz6",
            "layout": {},
            "paint": {}
        },
        {
            "id": "tunnel-street-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels-case"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 0%, 0%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ],
                "line-dasharray": [3, 3]
            }
        },
        {
            "id": "tunnel-minor-link-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels-case"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["primary_link", "secondary_link", "tertiary_link"],
                    true,
                    false
                ],
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.4,
                    18,
                    18,
                    22,
                    180
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-secondary-tertiary-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels-case"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 11,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["secondary", "tertiary"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 0%, 0%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0,
                    18,
                    26,
                    22,
                    260
                ],
                "line-dasharray": [3, 3],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-primary-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels-case"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 9,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["get", "class"], "primary"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 0%, 0%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    28,
                    22,
                    280
                ],
                "line-dasharray": [3, 3],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-major-link-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels-case"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-dasharray": [3, 3],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-motorway-trunk-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels-case"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["match", ["get", "class"], ["motorway", "trunk"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-dasharray": [3, 3],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-path",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["get", "class"], "path"],
                ["!=", ["get", "type"], "steps"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    15,
                    1,
                    18,
                    4
                ],
                "line-color": "hsl(0, 0%, 10%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.75, 1]],
                    16,
                    ["literal", [1, 0.75]],
                    17,
                    ["literal", [1, 0.5]]
                ]
            }
        },
        {
            "id": "tunnel-steps",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["get", "type"], "steps"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    15,
                    1,
                    16,
                    1.6,
                    18,
                    6
                ],
                "line-color": "hsl(0, 0%, 10%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.75, 1]],
                    16,
                    ["literal", [1, 0.75]],
                    17,
                    ["literal", [0.3, 0.3]]
                ]
            }
        },
        {
            "id": "tunnel-pedestrian",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["get", "class"], "pedestrian"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.5,
                    18,
                    12
                ],
                "line-color": "hsl(0, 0%, 10%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.5, 0.4]],
                    16,
                    ["literal", [1, 0.2]]
                ]
            }
        },
        {
            "id": "tunnel-minor-link",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["primary_link", "secondary_link", "tertiary_link"],
                    true,
                    false
                ],
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 13, "round"],
                "line-join": ["step", ["zoom"], "miter", 13, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.4,
                    18,
                    18,
                    22,
                    180
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-major-link",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway_link",
                    "hsla(26, 100%, 78%, 0.7)",
                    "hsla(46, 77%, 78%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-street",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "street_limited",
                    "hsl(0, 2%, 22%)",
                    "hsla(0, 0%, 90%, 0.5)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-street-low",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "maxzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-secondary-tertiary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["secondary", "tertiary"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0,
                    18,
                    26,
                    22,
                    260
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-primary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["get", "class"], "primary"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    28,
                    22,
                    280
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-motorway-trunk",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["match", ["get", "class"], ["motorway", "trunk"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway",
                    "hsla(26, 100%, 78%, 0.7)",
                    "hsla(46, 77%, 78%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "tunnel-oneway-arrow-blue",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 16,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                ["==", ["get", "oneway"], "true"],
                [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        [
                            "primary",
                            "secondary",
                            "street",
                            "street_limited",
                            "tertiary"
                        ],
                        true,
                        false
                    ],
                    16,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "primary",
                            "secondary",
                            "tertiary",
                            "street",
                            "street_limited",
                            "primary_link",
                            "secondary_link",
                            "tertiary_link"
                        ],
                        true,
                        false
                    ]
                ]
            ],
            "layout": {
                "symbol-placement": "line",
                "icon-image": [
                    "step",
                    ["zoom"],
                    "oneway-small",
                    18,
                    "oneway-large"
                ],
                "symbol-spacing": 200,
                "icon-rotation-alignment": "map",
                "icon-allow-overlap": true,
                "icon-ignore-placement": true
            },
            "paint": {}
        },
        {
            "id": "tunnel-oneway-arrow-white",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, tunnels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 16,
            "filter": [
                "all",
                ["==", ["get", "structure"], "tunnel"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway", "motorway_link", "trunk", "trunk_link"],
                    true,
                    false
                ],
                ["==", ["get", "oneway"], "true"]
            ],
            "layout": {
                "symbol-placement": "line",
                "icon-image": [
                    "step",
                    ["zoom"],
                    "oneway-white-small",
                    18,
                    "oneway-white-large"
                ],
                "symbol-spacing": 200,
                "icon-rotation-alignment": "map",
                "icon-allow-overlap": true,
                "icon-ignore-placement": true
            },
            "paint": {}
        },
        {
            "id": "road-path",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "class"], "path"],
                [
                    "step",
                    ["zoom"],
                    [
                        "!",
                        [
                            "match",
                            ["get", "type"],
                            ["steps", "sidewalk", "crossing"],
                            true,
                            false
                        ]
                    ],
                    16,
                    ["!=", ["get", "type"], "steps"]
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-join": ["step", ["zoom"], "miter", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    13,
                    0.5,
                    14,
                    1,
                    15,
                    1,
                    18,
                    4
                ],
                "line-color": "hsl(0, 0%, 22%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [4, 0.3]],
                    15,
                    ["literal", [1.75, 0.3]],
                    16,
                    ["literal", [1, 0.3]],
                    17,
                    ["literal", [1, 0.25]]
                ]
            }
        },
        {
            "id": "road-steps",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "type"], "steps"],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-join": "round"},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    15,
                    1,
                    16,
                    1.6,
                    18,
                    6
                ],
                "line-color": "hsl(0, 0%, 22%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.75, 1]],
                    16,
                    ["literal", [1, 0.75]],
                    17,
                    ["literal", [0.3, 0.3]]
                ]
            }
        },
        {
            "id": "road-pedestrian",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "class"], "pedestrian"],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["case", ["has", "layer"], [">=", ["get", "layer"], 0], true],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-join": ["step", ["zoom"], "miter", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.5,
                    18,
                    12
                ],
                "line-color": "hsl(0, 0%, 22%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.5, 0.4]],
                    16,
                    ["literal", [1, 0.2]]
                ]
            }
        },
        {
            "id": "road-street-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-minor-link-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["primary_link", "secondary_link", "tertiary_link"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.4,
                    18,
                    18,
                    22,
                    180
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-secondary-tertiary-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 11,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["secondary", "tertiary"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0,
                    18,
                    26,
                    22,
                    260
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-primary-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 9,
            "filter": [
                "all",
                ["==", ["get", "class"], "primary"],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    28,
                    22,
                    280
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-major-link-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-motorway-trunk-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 3,
            "filter": [
                "all",
                [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        ["motorway", "trunk"],
                        true,
                        false
                    ],
                    5,
                    [
                        "all",
                        [
                            "match",
                            ["get", "class"],
                            ["motorway", "trunk"],
                            true,
                            false
                        ],
                        [
                            "match",
                            ["get", "structure"],
                            ["none", "ford"],
                            true,
                            false
                        ]
                    ]
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    0,
                    3.5,
                    1,
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-minor-link",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["primary_link", "secondary_link", "tertiary_link"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 13, "round"],
                "line-join": ["step", ["zoom"], "miter", 13, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.4,
                    18,
                    18,
                    22,
                    180
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-major-link",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 13, "round"],
                "line-join": ["step", ["zoom"], "miter", 13, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway_link",
                    "hsla(26, 100%, 68%, 0.7)",
                    "hsla(46, 87%, 68%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-street",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "street_limited",
                    "hsl(0, 2%, 22%)",
                    "hsla(0, 0%, 90%, 0.5)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-street-low",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 11,
            "maxzoom": 14,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-secondary-tertiary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 8,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["secondary", "tertiary"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0,
                    18,
                    26,
                    22,
                    260
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-primary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 6,
            "filter": [
                "all",
                ["==", ["get", "class"], "primary"],
                ["match", ["get", "structure"], ["none", "ford"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    28,
                    22,
                    280
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-motorway-trunk",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 3,
            "filter": [
                "all",
                [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        ["motorway", "trunk"],
                        true,
                        false
                    ],
                    5,
                    [
                        "all",
                        [
                            "match",
                            ["get", "class"],
                            ["motorway", "trunk"],
                            true,
                            false
                        ],
                        [
                            "match",
                            ["get", "structure"],
                            ["none", "ford"],
                            true,
                            false
                        ]
                    ]
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 13, "round"],
                "line-join": ["step", ["zoom"], "miter", 13, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-color": [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        "motorway",
                        "hsla(26, 87%, 62%, 0.7)",
                        "trunk",
                        "hsla(46, 80%, 52%, 0.7)",
                        "hsl(0, 0%, 22%)"
                    ],
                    9,
                    [
                        "match",
                        ["get", "class"],
                        "motorway",
                        "hsla(26, 100%, 68%, 0.7)",
                        "hsla(46, 87%, 68%, 0.7)"
                    ]
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    0,
                    3.5,
                    1,
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "road-oneway-arrow-blue",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface-icons"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 16,
            "filter": [
                "all",
                ["==", ["get", "oneway"], "true"],
                [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        [
                            "primary",
                            "secondary",
                            "tertiary",
                            "street",
                            "street_limited"
                        ],
                        true,
                        false
                    ],
                    16,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "primary",
                            "secondary",
                            "tertiary",
                            "street",
                            "street_limited",
                            "primary_link",
                            "secondary_link",
                            "tertiary_link"
                        ],
                        true,
                        false
                    ]
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false]
            ],
            "layout": {
                "symbol-placement": "line",
                "icon-image": [
                    "step",
                    ["zoom"],
                    "oneway-small",
                    18,
                    "oneway-large"
                ],
                "symbol-spacing": 200,
                "icon-rotation-alignment": "map",
                "icon-allow-overlap": true,
                "icon-ignore-placement": true
            },
            "paint": {}
        },
        {
            "id": "road-oneway-arrow-white",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, surface-icons"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 16,
            "filter": [
                "all",
                ["==", ["get", "oneway"], "true"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway", "trunk", "motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["match", ["get", "structure"], ["none", "ford"], true, false]
            ],
            "layout": {
                "symbol-placement": "line",
                "icon-image": [
                    "step",
                    ["zoom"],
                    "oneway-white-small",
                    18,
                    "oneway-white-large"
                ],
                "symbol-spacing": 200,
                "icon-rotation-alignment": "map",
                "icon-allow-overlap": true,
                "icon-ignore-placement": true
            },
            "paint": {}
        },
        {
            "id": "bridge-path",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., barriers-bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["==", ["get", "class"], "path"],
                ["!=", ["get", "type"], "steps"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    15,
                    1,
                    18,
                    4
                ],
                "line-color": "hsl(0, 0%, 22%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [4, 0.3]],
                    15,
                    ["literal", [1.75, 0.3]],
                    16,
                    ["literal", [1, 0.3]],
                    17,
                    ["literal", [1, 0.25]]
                ]
            }
        },
        {
            "id": "bridge-steps",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., barriers-bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "type"], "steps"],
                ["==", ["get", "structure"], "bridge"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    15,
                    1,
                    16,
                    1.6,
                    18,
                    6
                ],
                "line-color": "hsl(0, 0%, 22%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.75, 1]],
                    16,
                    ["literal", [1, 0.75]],
                    17,
                    ["literal", [0.3, 0.3]]
                ]
            }
        },
        {
            "id": "bridge-pedestrian",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "walking-cycling",
                "mapbox:group": "Walking, cycling, etc., barriers-bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["==", ["get", "class"], "pedestrian"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.5,
                    18,
                    12
                ],
                "line-color": "hsl(0, 0%, 22%)",
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [1, 0]],
                    15,
                    ["literal", [1.5, 0.4]],
                    16,
                    ["literal", [1, 0.2]]
                ]
            }
        },
        {
            "id": "bridge-street-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-minor-link-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["primary_link", "secondary_link", "tertiary_link"],
                    true,
                    false
                ],
                ["==", ["get", "structure"], "bridge"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-join": ["step", ["zoom"], "miter", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.4,
                    18,
                    18,
                    22,
                    180
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-secondary-tertiary-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 11,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["secondary", "tertiary"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0,
                    18,
                    26,
                    22,
                    260
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-primary-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 9,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["==", ["get", "class"], "primary"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 10%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    28,
                    22,
                    280
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-major-link-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["<=", ["get", "layer"], 1],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-motorway-trunk-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["match", ["get", "class"], ["motorway", "trunk"], true, false],
                ["<=", ["get", "layer"], 1],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-minor-link",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    ["primary_link", "secondary_link", "tertiary_link"],
                    true,
                    false
                ],
                ["==", ["get", "structure"], "bridge"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": "round"},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.4,
                    18,
                    18,
                    22,
                    180
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-major-link",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["<=", ["get", "layer"], 1],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": "round"},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway_link",
                    "hsla(26, 100%, 68%, 0.7)",
                    "hsla(46, 87%, 68%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-street",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": ["step", ["zoom"], "butt", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "street_limited",
                    "hsl(0, 2%, 22%)",
                    "hsla(0, 0%, 90%, 0.5)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-street-low",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "maxzoom": 14,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["street", "street_limited"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "line-cap": ["step", ["zoom"], "butt", 14, "round"],
                "line-join": ["step", ["zoom"], "miter", 14, "round"]
            },
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.5,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-secondary-tertiary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["secondary", "tertiary"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": ["step", ["zoom"], "butt", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0,
                    18,
                    26,
                    22,
                    260
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-primary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["==", ["get", "class"], "primary"],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": ["step", ["zoom"], "butt", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    28,
                    22,
                    280
                ],
                "line-color": "hsla(0, 0%, 90%, 0.5)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    0.3,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-motorway-trunk",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["match", ["get", "class"], ["motorway", "trunk"], true, false],
                ["<=", ["get", "layer"], 1],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": "round"},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway",
                    "hsla(26, 100%, 68%, 0.7)",
                    "hsla(46, 87%, 68%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-major-link-2-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [">=", ["get", "layer"], 2],
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    0.8,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-motorway-trunk-2-case",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [">=", ["get", "layer"], 2],
                ["match", ["get", "class"], ["motorway", "trunk"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    22,
                    2
                ],
                "line-color": "hsla(0, 1%, 22%, 0.5)",
                "line-gap-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-major-link-2",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [">=", ["get", "layer"], 2],
                [
                    "match",
                    ["get", "class"],
                    ["motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": "round"},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    12,
                    0.8,
                    18,
                    20,
                    22,
                    200
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway_link",
                    "hsla(26, 100%, 68%, 0.7)",
                    "hsla(46, 87%, 68%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-motorway-trunk-2",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 13,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [">=", ["get", "layer"], 2],
                ["match", ["get", "class"], ["motorway", "trunk"], true, false],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {"line-cap": ["step", ["zoom"], "butt", 14, "round"]},
            "paint": {
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    3,
                    0.8,
                    18,
                    30,
                    22,
                    300
                ],
                "line-color": [
                    "match",
                    ["get", "class"],
                    "motorway",
                    "hsla(26, 100%, 68%, 0.7)",
                    "hsla(46, 87%, 68%, 0.7)"
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    1,
                    15,
                    0
                ]
            }
        },
        {
            "id": "bridge-oneway-arrow-blue",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 16,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                ["==", ["get", "oneway"], "true"],
                [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        [
                            "primary",
                            "secondary",
                            "tertiary",
                            "street",
                            "street_limited"
                        ],
                        true,
                        false
                    ],
                    16,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "primary",
                            "secondary",
                            "tertiary",
                            "street",
                            "street_limited",
                            "primary_link",
                            "secondary_link",
                            "tertiary_link"
                        ],
                        true,
                        false
                    ]
                ]
            ],
            "layout": {
                "symbol-placement": "line",
                "icon-image": [
                    "step",
                    ["zoom"],
                    "oneway-small",
                    18,
                    "oneway-large"
                ],
                "symbol-spacing": 200,
                "icon-rotation-alignment": "map",
                "icon-allow-overlap": true,
                "icon-ignore-placement": true
            },
            "paint": {}
        },
        {
            "id": "bridge-oneway-arrow-white",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, bridges"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 16,
            "filter": [
                "all",
                ["==", ["get", "structure"], "bridge"],
                [
                    "match",
                    ["get", "class"],
                    ["motorway", "trunk", "motorway_link", "trunk_link"],
                    true,
                    false
                ],
                ["==", ["get", "oneway"], "true"]
            ],
            "layout": {
                "symbol-placement": "line",
                "icon-image": "oneway-white-small",
                "symbol-spacing": 200,
                "icon-rotation-alignment": "map",
                "icon-allow-overlap": true,
                "icon-ignore-placement": true
            },
            "paint": {}
        },
        {
            "id": "aerialway",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "transit",
                "mapbox:group": "Transit, elevated"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 12,
            "filter": ["==", ["get", "class"], "aerialway"],
            "paint": {
                "line-color": "hsl(0, 0%, 100%)",
                "line-width": [
                    "interpolate",
                    ["exponential", 1.5],
                    ["zoom"],
                    14,
                    1,
                    20,
                    2
                ],
                "line-dasharray": [4, 1]
            }
        },
        {
            "id": "admin-1-boundary-bg",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "admin-boundaries",
                "mapbox:group": "Administrative boundaries, admin"
            },
            "source": "composite",
            "source-layer": "admin",
            "minzoom": 7,
            "filter": [
                "all",
                ["==", ["get", "admin_level"], 1],
                ["==", ["get", "maritime"], "false"],
                ["match", ["get", "worldview"], ["all", "US"], true, false]
            ],
            "paint": {
                "line-color": "hsl(0, 0%, 30%)",
                "line-width": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    3,
                    12,
                    6
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    7,
                    0,
                    8,
                    0.5
                ],
                "line-dasharray": [1, 0],
                "line-blur": ["interpolate", ["linear"], ["zoom"], 3, 0, 12, 3]
            }
        },
        {
            "id": "admin-0-boundary-bg",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "admin-boundaries",
                "mapbox:group": "Administrative boundaries, admin"
            },
            "source": "composite",
            "source-layer": "admin",
            "minzoom": 1,
            "filter": [
                "all",
                ["==", ["get", "admin_level"], 0],
                ["==", ["get", "maritime"], "false"],
                ["match", ["get", "worldview"], ["all", "US"], true, false]
            ],
            "paint": {
                "line-width": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    6,
                    12,
                    12
                ],
                "line-color": "hsl(0, 0%, 30%)",
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    0,
                    4,
                    0.5
                ],
                "line-blur": ["interpolate", ["linear"], ["zoom"], 3, 0, 12, 3]
            }
        },
        {
            "id": "admin-1-boundary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "admin-boundaries",
                "mapbox:group": "Administrative boundaries, admin"
            },
            "source": "composite",
            "source-layer": "admin",
            "minzoom": 2,
            "filter": [
                "all",
                ["==", ["get", "admin_level"], 1],
                ["==", ["get", "maritime"], "false"],
                ["match", ["get", "worldview"], ["all", "US"], true, false]
            ],
            "layout": {},
            "paint": {
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [2, 0]],
                    7,
                    ["literal", [2, 2, 6, 2]]
                ],
                "line-width": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    0.3,
                    12,
                    1.5
                ],
                "line-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    2,
                    0,
                    3,
                    1
                ],
                "line-color": "hsl(0, 0%, 5%)"
            }
        },
        {
            "id": "admin-0-boundary",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "admin-boundaries",
                "mapbox:group": "Administrative boundaries, admin"
            },
            "source": "composite",
            "source-layer": "admin",
            "minzoom": 1,
            "filter": [
                "all",
                ["==", ["get", "admin_level"], 0],
                ["==", ["get", "disputed"], "false"],
                ["==", ["get", "maritime"], "false"],
                ["match", ["get", "worldview"], ["all", "US"], true, false]
            ],
            "layout": {},
            "paint": {
                "line-color": "hsl(0, 0%, 0%)",
                "line-width": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    0.75,
                    12,
                    3
                ],
                "line-dasharray": [10, 0]
            }
        },
        {
            "id": "admin-0-boundary-disputed",
            "type": "line",
            "metadata": {
                "mapbox:featureComponent": "admin-boundaries",
                "mapbox:group": "Administrative boundaries, admin"
            },
            "source": "composite",
            "source-layer": "admin",
            "minzoom": 1,
            "filter": [
                "all",
                ["==", ["get", "disputed"], "true"],
                ["==", ["get", "admin_level"], 0],
                ["==", ["get", "maritime"], "false"],
                ["match", ["get", "worldview"], ["all", "US"], true, false]
            ],
            "paint": {
                "line-color": "hsl(0, 0%, 0%)",
                "line-width": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    3,
                    0.75,
                    12,
                    3
                ],
                "line-dasharray": [
                    "step",
                    ["zoom"],
                    ["literal", [3, 2, 5]],
                    7,
                    ["literal", [2, 1.5]]
                ]
            }
        },
        {
            "id": "road-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, road-labels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 10,
            "filter": [
                "all",
                ["has", "name"],
                [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "class"],
                        [
                            "motorway",
                            "trunk",
                            "primary",
                            "secondary",
                            "tertiary"
                        ],
                        true,
                        false
                    ],
                    12,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "motorway",
                            "trunk",
                            "primary",
                            "secondary",
                            "tertiary",
                            "street",
                            "street_limited"
                        ],
                        true,
                        false
                    ],
                    15,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "path",
                            "pedestrian",
                            "golf",
                            "ferry",
                            "aerialway",
                            "track",
                            "service"
                        ],
                        false,
                        true
                    ]
                ]
            ],
            "layout": {
                "text-size": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    10,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "motorway",
                            "trunk",
                            "primary",
                            "secondary",
                            "tertiary"
                        ],
                        10,
                        [
                            "motorway_link",
                            "trunk_link",
                            "primary_link",
                            "secondary_link",
                            "tertiary_link",
                            "street",
                            "street_limited"
                        ],
                        9,
                        6.5
                    ],
                    18,
                    [
                        "match",
                        ["get", "class"],
                        [
                            "motorway",
                            "trunk",
                            "primary",
                            "secondary",
                            "tertiary"
                        ],
                        16,
                        [
                            "motorway_link",
                            "trunk_link",
                            "primary_link",
                            "secondary_link",
                            "tertiary_link",
                            "street",
                            "street_limited"
                        ],
                        14,
                        13
                    ]
                ],
                "text-max-angle": 30,
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "symbol-placement": "line",
                "text-padding": 1,
                "text-rotation-alignment": "map",
                "text-pitch-alignment": "viewport",
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-letter-spacing": 0.01
            },
            "paint": {
                "text-color": "hsl(0, 0%, 100%)",
                "text-halo-color": [
                    "match",
                    ["get", "class"],
                    ["motorway", "trunk"],
                    "hsla(0, 5%, 0%, 0.75)",
                    "hsl(0, 5%, 0%)"
                ],
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "road-intersection",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, road-labels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 15,
            "filter": [
                "all",
                ["==", ["get", "class"], "intersection"],
                ["has", "name"]
            ],
            "layout": {
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "icon-image": "intersection",
                "icon-text-fit": "both",
                "icon-text-fit-padding": [1, 2, 1, 2],
                "text-size": [
                    "interpolate",
                    ["exponential", 1.2],
                    ["zoom"],
                    15,
                    9,
                    18,
                    12
                ],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"]
            },
            "paint": {"text-color": "hsl(230, 57%, 64%)"}
        },
        {
            "id": "road-number-shield",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, road-labels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 6,
            "filter": [
                "all",
                ["has", "reflen"],
                ["<=", ["get", "reflen"], 6],
                [
                    "match",
                    ["get", "class"],
                    ["pedestrian", "service"],
                    false,
                    true
                ],
                [
                    "step",
                    ["zoom"],
                    ["==", ["geometry-type"], "Point"],
                    11,
                    [">", ["get", "len"], 5000],
                    12,
                    [">", ["get", "len"], 2500],
                    13,
                    [">", ["get", "len"], 1000],
                    14,
                    true
                ]
            ],
            "layout": {
                "text-size": 9,
                "icon-image": [
                    "case",
                    ["has", "shield_beta"],
                    [
                        "coalesce",
                        [
                            "image",
                            [
                                "concat",
                                ["get", "shield_beta"],
                                "-",
                                ["to-string", ["get", "reflen"]]
                            ]
                        ],
                        [
                            "image",
                            [
                                "concat",
                                "default-",
                                ["to-string", ["get", "reflen"]]
                            ]
                        ]
                    ],
                    [
                        "concat",
                        ["get", "shield"],
                        "-",
                        ["to-string", ["get", "reflen"]]
                    ]
                ],
                "icon-rotation-alignment": "viewport",
                "text-max-angle": 38,
                "symbol-spacing": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    11,
                    400,
                    14,
                    600
                ],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "symbol-placement": ["step", ["zoom"], "point", 11, "line"],
                "text-rotation-alignment": "viewport",
                "text-field": ["get", "ref"],
                "text-letter-spacing": 0.05
            },
            "paint": {
                "text-color": [
                    "case",
                    [
                        "all",
                        ["has", "shield_text_color_beta"],
                        [
                            "to-boolean",
                            [
                                "coalesce",
                                [
                                    "image",
                                    [
                                        "concat",
                                        ["get", "shield_beta"],
                                        "-",
                                        ["to-string", ["get", "reflen"]]
                                    ]
                                ],
                                ""
                            ]
                        ]
                    ],
                    [
                        "match",
                        ["get", "shield_text_color_beta"],
                        "white",
                        "hsl(0, 0%, 100%)",
                        "yellow",
                        "hsl(50, 100%, 70%)",
                        "orange",
                        "hsl(25, 100%, 75%)",
                        "blue",
                        "hsl(230, 57%, 44%)",
                        "red",
                        "hsl(0, 87%, 59%)",
                        "green",
                        "hsl(140, 74%, 37%)",
                        "hsl(230, 18%, 13%)"
                    ],
                    [
                        "match",
                        ["get", "shield_text_color"],
                        "white",
                        "hsl(0, 0%, 100%)",
                        "yellow",
                        "hsl(50, 100%, 70%)",
                        "orange",
                        "hsl(25, 100%, 75%)",
                        "blue",
                        "hsl(230, 57%, 44%)",
                        "red",
                        "hsl(0, 87%, 59%)",
                        "green",
                        "hsl(140, 74%, 37%)",
                        "hsl(230, 18%, 13%)"
                    ]
                ]
            }
        },
        {
            "id": "road-exit-shield",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "road-network",
                "mapbox:group": "Road network, road-labels"
            },
            "source": "composite",
            "source-layer": "motorway_junction",
            "minzoom": 14,
            "filter": ["all", ["has", "reflen"], ["<=", ["get", "reflen"], 9]],
            "layout": {
                "text-field": ["get", "ref"],
                "text-size": 9,
                "icon-image": [
                    "concat",
                    "motorway-exit-",
                    ["to-string", ["get", "reflen"]]
                ],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"]
            },
            "paint": {
                "text-color": "hsl(0, 0%, 100%)",
                "text-translate": [0, 0]
            }
        },
        {
            "id": "ferry-aerialway-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "transit",
                "mapbox:group": "Transit, ferry-aerialway-labels"
            },
            "source": "composite",
            "source-layer": "road",
            "minzoom": 15,
            "filter": ["match", ["get", "class"], "aerialway", true, false],
            "layout": {
                "text-size": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    10,
                    6.5,
                    18,
                    13
                ],
                "text-max-angle": 30,
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "symbol-placement": "line",
                "text-padding": 1,
                "text-rotation-alignment": "map",
                "text-pitch-alignment": "viewport",
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-letter-spacing": 0.01
            },
            "paint": {
                "text-color": [
                    "match",
                    ["get", "class"],
                    "ferry",
                    "hsl(196, 18%, 86%)",
                    "hsl(0, 0%, 100%)"
                ],
                "text-halo-color": [
                    "match",
                    ["get", "class"],
                    "ferry",
                    "hsl(196, 50%, 50%)",
                    "hsl(0, 0%, 0%)"
                ],
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "waterway-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "natural-features",
                "mapbox:group": "Natural features, natural-labels"
            },
            "source": "composite",
            "source-layer": "natural_label",
            "minzoom": 13,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    [
                        "canal",
                        "river",
                        "stream",
                        "disputed_canal",
                        "disputed_river",
                        "disputed_stream"
                    ],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "text-font": ["DIN Pro Italic", "Arial Unicode MS Regular"],
                "text-max-angle": 30,
                "symbol-spacing": [
                    "interpolate",
                    ["linear", 1],
                    ["zoom"],
                    15,
                    250,
                    17,
                    400
                ],
                "text-size": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    13,
                    12,
                    18,
                    18
                ],
                "symbol-placement": "line",
                "text-pitch-alignment": "viewport",
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]]
            },
            "paint": {
                "text-color": "hsl(196, 18%, 90%)",
                "text-halo-color": "hsla(0, 0%, 0%, 0.5)",
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "natural-line-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "natural-features",
                "mapbox:group": "Natural features, natural-labels"
            },
            "source": "composite",
            "source-layer": "natural_label",
            "minzoom": 4,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    [
                        "glacier",
                        "landform",
                        "disputed_glacier",
                        "disputed_landform"
                    ],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                ["<=", ["get", "filterrank"], 2],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "text-size": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], 18, 5, 12],
                    17,
                    ["step", ["get", "sizerank"], 18, 13, 12]
                ],
                "text-max-angle": 30,
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "symbol-placement": "line-center",
                "text-pitch-alignment": "viewport"
            },
            "paint": {
                "text-halo-width": 0.5,
                "text-halo-color": "hsl(0, 0%, 0%)",
                "text-halo-blur": 0.5,
                "text-color": "hsl(0, 0%, 100%)"
            }
        },
        {
            "id": "natural-point-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "natural-features",
                "mapbox:group": "Natural features, natural-labels"
            },
            "source": "composite",
            "source-layer": "natural_label",
            "minzoom": 4,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    [
                        "dock",
                        "glacier",
                        "landform",
                        "water_feature",
                        "wetland",
                        "disputed_dock",
                        "disputed_glacier",
                        "disputed_landform",
                        "disputed_water_feature",
                        "disputed_wetland"
                    ],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                ["<=", ["get", "filterrank"], 2],
                ["==", ["geometry-type"], "Point"]
            ],
            "layout": {
                "text-size": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], 18, 5, 12],
                    17,
                    ["step", ["get", "sizerank"], 18, 13, 12]
                ],
                "icon-image": ["get", "maki"],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "text-offset": [
                    "step",
                    ["zoom"],
                    [
                        "step",
                        ["get", "sizerank"],
                        ["literal", [0, 0]],
                        5,
                        ["literal", [0, 0.75]]
                    ],
                    17,
                    [
                        "step",
                        ["get", "sizerank"],
                        ["literal", [0, 0]],
                        13,
                        ["literal", [0, 0.75]]
                    ]
                ],
                "text-anchor": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], "center", 5, "top"],
                    17,
                    ["step", ["get", "sizerank"], "center", 13, "top"]
                ],
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]]
            },
            "paint": {
                "icon-opacity": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], 0, 5, 1],
                    17,
                    ["step", ["get", "sizerank"], 0, 13, 1]
                ],
                "text-halo-color": "hsl(0, 0%, 0%)",
                "text-halo-width": 0.5,
                "text-halo-blur": 0.5,
                "text-color": "hsl(0, 0%, 100%)"
            }
        },
        {
            "id": "water-line-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "natural-features",
                "mapbox:group": "Natural features, natural-labels"
            },
            "source": "composite",
            "source-layer": "natural_label",
            "minzoom": 1,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    [
                        "bay",
                        "ocean",
                        "reservoir",
                        "sea",
                        "water",
                        "disputed_bay",
                        "disputed_ocean",
                        "disputed_reservoir",
                        "disputed_sea",
                        "disputed_water"
                    ],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                ["==", ["geometry-type"], "LineString"]
            ],
            "layout": {
                "text-size": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    0,
                    ["*", ["-", 16, ["sqrt", ["get", "sizerank"]]], 1],
                    22,
                    ["*", ["-", 22, ["sqrt", ["get", "sizerank"]]], 1]
                ],
                "text-max-angle": 30,
                "text-letter-spacing": [
                    "match",
                    ["get", "class"],
                    "ocean",
                    0.25,
                    ["sea", "bay"],
                    0.15,
                    0
                ],
                "text-font": ["DIN Pro Italic", "Arial Unicode MS Regular"],
                "symbol-placement": "line-center",
                "text-pitch-alignment": "viewport",
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]]
            },
            "paint": {
                "text-color": [
                    "match",
                    ["get", "class"],
                    ["bay", "ocean", "sea"],
                    "hsl(196, 46%, 70%)",
                    "hsl(196, 18%, 90%)"
                ],
                "text-halo-color": "hsla(0, 0%, 0%, 0.5)",
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "water-point-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "natural-features",
                "mapbox:group": "Natural features, natural-labels"
            },
            "source": "composite",
            "source-layer": "natural_label",
            "minzoom": 1,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    [
                        "bay",
                        "ocean",
                        "reservoir",
                        "sea",
                        "water",
                        "disputed_bay",
                        "disputed_ocean",
                        "disputed_reservoir",
                        "disputed_sea",
                        "disputed_water"
                    ],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                ["==", ["geometry-type"], "Point"]
            ],
            "layout": {
                "text-line-height": 1.3,
                "text-size": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    0,
                    ["*", ["-", 16, ["sqrt", ["get", "sizerank"]]], 1],
                    22,
                    ["*", ["-", 22, ["sqrt", ["get", "sizerank"]]], 1]
                ],
                "text-font": ["DIN Pro Italic", "Arial Unicode MS Regular"],
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-letter-spacing": [
                    "match",
                    ["get", "class"],
                    "ocean",
                    0.25,
                    ["bay", "sea"],
                    0.15,
                    0.01
                ],
                "text-max-width": [
                    "match",
                    ["get", "class"],
                    "ocean",
                    4,
                    "sea",
                    5,
                    ["bay", "water"],
                    7,
                    10
                ]
            },
            "paint": {
                "text-color": [
                    "match",
                    ["get", "class"],
                    ["bay", "ocean", "sea"],
                    "hsl(196, 46%, 70%)",
                    "hsl(196, 18%, 90%)"
                ],
                "text-halo-color": "hsla(0, 0%, 0%, 0.5)",
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "poi-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "point-of-interest-labels",
                "mapbox:group": "Point of interest labels, poi-labels"
            },
            "source": "composite",
            "source-layer": "poi_label",
            "minzoom": 6,
            "filter": [
                "<=",
                ["get", "filterrank"],
                ["+", ["step", ["zoom"], 0, 16, 1, 17, 2], 3]
            ],
            "layout": {
                "text-size": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], 18, 5, 12],
                    17,
                    ["step", ["get", "sizerank"], 18, 13, 12]
                ],
                "icon-image": [
                    "case",
                    ["has", "maki_beta"],
                    [
                        "coalesce",
                        ["image", ["get", "maki_beta"]],
                        ["image", ["get", "maki"]]
                    ],
                    ["image", ["get", "maki"]]
                ],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "text-offset": [
                    "step",
                    ["zoom"],
                    [
                        "step",
                        ["get", "sizerank"],
                        ["literal", [0, 0]],
                        5,
                        ["literal", [0, 0.75]]
                    ],
                    17,
                    [
                        "step",
                        ["get", "sizerank"],
                        ["literal", [0, 0]],
                        13,
                        ["literal", [0, 0.75]]
                    ]
                ],
                "text-anchor": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], "center", 5, "top"],
                    17,
                    ["step", ["get", "sizerank"], "center", 13, "top"]
                ],
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]]
            },
            "paint": {
                "icon-opacity": [
                    "step",
                    ["zoom"],
                    ["step", ["get", "sizerank"], 0, 5, 1],
                    17,
                    ["step", ["get", "sizerank"], 0, 13, 1]
                ],
                "text-halo-color": "hsl(0, 0%, 0%)",
                "text-halo-width": 0.5,
                "text-halo-blur": 0.5,
                "text-color": "hsl(0, 0%, 100%)"
            }
        },
        {
            "id": "transit-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "transit",
                "mapbox:group": "Transit, transit-labels"
            },
            "source": "composite",
            "source-layer": "transit_stop_label",
            "minzoom": 12,
            "filter": [
                "step",
                ["zoom"],
                [
                    "all",
                    ["<=", ["get", "filterrank"], 4],
                    [
                        "match",
                        ["get", "mode"],
                        "rail",
                        true,
                        "metro_rail",
                        true,
                        false
                    ],
                    ["!=", ["get", "stop_type"], "entrance"]
                ],
                14,
                [
                    "all",
                    [
                        "match",
                        ["get", "mode"],
                        "rail",
                        true,
                        "metro_rail",
                        true,
                        false
                    ],
                    ["!=", ["get", "stop_type"], "entrance"]
                ],
                15,
                [
                    "all",
                    [
                        "match",
                        ["get", "mode"],
                        "rail",
                        true,
                        "metro_rail",
                        true,
                        "light_rail",
                        true,
                        false
                    ],
                    ["!=", ["get", "stop_type"], "entrance"]
                ],
                16,
                [
                    "all",
                    [
                        "match",
                        ["get", "mode"],
                        "ferry",
                        false,
                        "bus",
                        false,
                        true
                    ],
                    ["!=", ["get", "stop_type"], "entrance"]
                ],
                17,
                [
                    "all",
                    ["match", ["get", "mode"], "ferry", false, true],
                    ["!=", ["get", "stop_type"], "entrance"]
                ],
                19,
                ["match", ["get", "mode"], "ferry", false, true]
            ],
            "layout": {
                "text-size": 12,
                "icon-image": ["get", "network"],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "text-justify": [
                    "match",
                    ["get", "stop_type"],
                    "entrance",
                    "left",
                    "center"
                ],
                "text-offset": [
                    "match",
                    ["get", "stop_type"],
                    "entrance",
                    ["literal", [1, 0]],
                    ["literal", [0, 0.75]]
                ],
                "text-anchor": [
                    "match",
                    ["get", "stop_type"],
                    "entrance",
                    "left",
                    "top"
                ],
                "text-field": [
                    "step",
                    ["zoom"],
                    "",
                    13,
                    [
                        "match",
                        ["get", "mode"],
                        ["rail", "metro_rail"],
                        ["coalesce", ["get", "name_en"], ["get", "name"]],
                        ""
                    ],
                    14,
                    [
                        "match",
                        ["get", "mode"],
                        ["bus", "bicycle"],
                        "",
                        ["coalesce", ["get", "name_en"], ["get", "name"]]
                    ],
                    18,
                    ["coalesce", ["get", "name_en"], ["get", "name"]]
                ],
                "text-letter-spacing": 0.01,
                "text-max-width": [
                    "match",
                    ["get", "stop_type"],
                    "entrance",
                    15,
                    9
                ]
            },
            "paint": {
                "text-halo-color": "hsl(0, 0%, 0%)",
                "text-color": "hsl(0, 0%, 100%)",
                "text-halo-blur": 0.5,
                "text-halo-width": 0.5
            }
        },
        {
            "id": "airport-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "transit",
                "mapbox:group": "Transit, transit-labels"
            },
            "source": "composite",
            "source-layer": "airport_label",
            "minzoom": 8,
            "filter": [
                "match",
                ["get", "class"],
                ["military", "civil", "disputed_military", "disputed_civil"],
                ["match", ["get", "worldview"], ["all", "US"], true, false],
                false
            ],
            "layout": {
                "text-line-height": 1.1,
                "text-size": ["step", ["get", "sizerank"], 18, 9, 12],
                "icon-image": ["get", "maki"],
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "text-offset": [0, 0.75],
                "text-rotation-alignment": "viewport",
                "text-anchor": "top",
                "text-field": [
                    "step",
                    ["get", "sizerank"],
                    [
                        "case",
                        ["has", "ref"],
                        [
                            "concat",
                            ["get", "ref"],
                            " -\n",
                            ["coalesce", ["get", "name_en"], ["get", "name"]]
                        ],
                        ["coalesce", ["get", "name_en"], ["get", "name"]]
                    ],
                    15,
                    ["get", "ref"]
                ],
                "text-letter-spacing": 0.01,
                "text-max-width": 9
            },
            "paint": {
                "text-color": "hsl(0, 0%, 100%)",
                "text-halo-color": "hsl(0, 0%, 0%)",
                "text-halo-width": 1
            }
        },
        {
            "id": "settlement-subdivision-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "place-labels",
                "mapbox:group": "Place labels, place-labels"
            },
            "source": "composite",
            "source-layer": "place_label",
            "minzoom": 10,
            "maxzoom": 15,
            "filter": [
                "all",
                [
                    "match",
                    ["get", "class"],
                    [
                        "settlement_subdivision",
                        "disputed_settlement_subdivision"
                    ],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                ["<=", ["get", "filterrank"], 4]
            ],
            "layout": {
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-transform": "uppercase",
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "text-letter-spacing": [
                    "match",
                    ["get", "type"],
                    "suburb",
                    0.15,
                    0.05
                ],
                "text-max-width": 7,
                "text-padding": 3,
                "text-size": [
                    "interpolate",
                    ["cubic-bezier", 0.5, 0, 1, 1],
                    ["zoom"],
                    11,
                    ["match", ["get", "type"], "suburb", 11, 10.5],
                    15,
                    ["match", ["get", "type"], "suburb", 15, 14]
                ]
            },
            "paint": {
                "text-halo-color": "hsla(0, 5%, 0%, 0.75)",
                "text-halo-width": 1,
                "text-color": "hsl(0, 0%, 100%)",
                "text-halo-blur": 0.5
            }
        },
        {
            "id": "settlement-minor-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "place-labels",
                "mapbox:group": "Place labels, place-labels"
            },
            "source": "composite",
            "source-layer": "place_label",
            "minzoom": 2,
            "maxzoom": 13,
            "filter": [
                "all",
                ["<=", ["get", "filterrank"], 3],
                [
                    "match",
                    ["get", "class"],
                    ["settlement", "disputed_settlement"],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                [
                    "step",
                    ["zoom"],
                    [">", ["get", "symbolrank"], 6],
                    4,
                    [">=", ["get", "symbolrank"], 7],
                    6,
                    [">=", ["get", "symbolrank"], 8],
                    7,
                    [">=", ["get", "symbolrank"], 10],
                    10,
                    [">=", ["get", "symbolrank"], 11],
                    11,
                    [">=", ["get", "symbolrank"], 13],
                    12,
                    [">=", ["get", "symbolrank"], 15]
                ]
            ],
            "layout": {
                "text-line-height": 1.1,
                "text-size": [
                    "interpolate",
                    ["cubic-bezier", 0.2, 0, 0.9, 1],
                    ["zoom"],
                    3,
                    ["step", ["get", "symbolrank"], 11, 9, 10],
                    6,
                    ["step", ["get", "symbolrank"], 14, 9, 12, 12, 10],
                    8,
                    ["step", ["get", "symbolrank"], 16, 9, 14, 12, 12, 15, 10],
                    13,
                    ["step", ["get", "symbolrank"], 22, 9, 20, 12, 16, 15, 14]
                ],
                "text-radial-offset": [
                    "step",
                    ["zoom"],
                    ["match", ["get", "capital"], 2, 0.6, 0.55],
                    8,
                    0
                ],
                "symbol-sort-key": ["get", "symbolrank"],
                "icon-image": [
                    "step",
                    ["zoom"],
                    [
                        "case",
                        ["==", ["get", "capital"], 2],
                        "border-dot-13",
                        [
                            "step",
                            ["get", "symbolrank"],
                            "dot-11",
                            9,
                            "dot-10",
                            11,
                            "dot-9"
                        ]
                    ],
                    8,
                    ""
                ],
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "text-justify": "auto",
                "text-anchor": [
                    "step",
                    ["zoom"],
                    ["get", "text_anchor"],
                    8,
                    "center"
                ],
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-max-width": 7
            },
            "paint": {
                "text-color": "hsl(0, 0%, 95%)",
                "text-halo-color": "hsl(0, 5%, 0%)",
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "settlement-major-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "place-labels",
                "mapbox:group": "Place labels, place-labels"
            },
            "source": "composite",
            "source-layer": "place_label",
            "minzoom": 2,
            "maxzoom": 15,
            "filter": [
                "all",
                ["<=", ["get", "filterrank"], 3],
                [
                    "match",
                    ["get", "class"],
                    ["settlement", "disputed_settlement"],
                    ["match", ["get", "worldview"], ["all", "US"], true, false],
                    false
                ],
                [
                    "step",
                    ["zoom"],
                    false,
                    2,
                    ["<=", ["get", "symbolrank"], 6],
                    4,
                    ["<", ["get", "symbolrank"], 7],
                    6,
                    ["<", ["get", "symbolrank"], 8],
                    7,
                    ["<", ["get", "symbolrank"], 10],
                    10,
                    ["<", ["get", "symbolrank"], 11],
                    11,
                    ["<", ["get", "symbolrank"], 13],
                    12,
                    ["<", ["get", "symbolrank"], 15],
                    13,
                    [">=", ["get", "symbolrank"], 11],
                    14,
                    [">=", ["get", "symbolrank"], 15]
                ]
            ],
            "layout": {
                "text-line-height": 1.1,
                "text-size": [
                    "interpolate",
                    ["cubic-bezier", 0.2, 0, 0.9, 1],
                    ["zoom"],
                    3,
                    ["step", ["get", "symbolrank"], 13, 6, 11],
                    6,
                    ["step", ["get", "symbolrank"], 18, 6, 16, 7, 14],
                    8,
                    ["step", ["get", "symbolrank"], 20, 9, 16, 10, 14],
                    15,
                    ["step", ["get", "symbolrank"], 24, 9, 20, 12, 16, 15, 14]
                ],
                "text-radial-offset": [
                    "step",
                    ["zoom"],
                    ["match", ["get", "capital"], 2, 0.6, 0.55],
                    8,
                    0
                ],
                "symbol-sort-key": ["get", "symbolrank"],
                "icon-image": [
                    "step",
                    ["zoom"],
                    [
                        "case",
                        ["==", ["get", "capital"], 2],
                        "border-dot-13",
                        [
                            "step",
                            ["get", "symbolrank"],
                            "dot-11",
                            9,
                            "dot-10",
                            11,
                            "dot-9"
                        ]
                    ],
                    8,
                    ""
                ],
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "text-justify": [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "text_anchor"],
                        ["left", "bottom-left", "top-left"],
                        "left",
                        ["right", "bottom-right", "top-right"],
                        "right",
                        "center"
                    ],
                    8,
                    "center"
                ],
                "text-anchor": [
                    "step",
                    ["zoom"],
                    ["get", "text_anchor"],
                    8,
                    "center"
                ],
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-max-width": 7
            },
            "paint": {
                "text-color": "hsl(0, 0%, 95%)",
                "text-halo-color": "hsl(0, 5%, 0%)",
                "text-halo-width": 1,
                "text-halo-blur": 1
            }
        },
        {
            "id": "state-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "place-labels",
                "mapbox:group": "Place labels, place-labels"
            },
            "source": "composite",
            "source-layer": "place_label",
            "minzoom": 3,
            "maxzoom": 9,
            "filter": [
                "match",
                ["get", "class"],
                ["state", "disputed_state"],
                ["match", ["get", "worldview"], ["all", "US"], true, false],
                false
            ],
            "layout": {
                "text-size": [
                    "interpolate",
                    ["cubic-bezier", 0.85, 0.7, 0.65, 1],
                    ["zoom"],
                    4,
                    ["step", ["get", "symbolrank"], 9, 6, 8, 7, 7],
                    9,
                    ["step", ["get", "symbolrank"], 21, 6, 16, 7, 14]
                ],
                "text-transform": "uppercase",
                "text-font": ["DIN Pro Bold", "Arial Unicode MS Bold"],
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-letter-spacing": 0.15,
                "text-max-width": 6
            },
            "paint": {
                "text-color": "hsl(0, 0%, 95%)",
                "text-halo-color": "hsl(0, 5%, 0%)",
                "text-halo-width": 1,
                "text-opacity": 0.5
            }
        },
        {
            "id": "country-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "place-labels",
                "mapbox:group": "Place labels, place-labels"
            },
            "source": "composite",
            "source-layer": "place_label",
            "minzoom": 1,
            "maxzoom": 10,
            "filter": [
                "match",
                ["get", "class"],
                ["country", "disputed_country"],
                ["match", ["get", "worldview"], ["all", "US"], true, false],
                false
            ],
            "layout": {
                "icon-image": "",
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-line-height": 1.1,
                "text-max-width": 6,
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "text-radial-offset": ["step", ["zoom"], 0.6, 8, 0],
                "text-justify": [
                    "step",
                    ["zoom"],
                    [
                        "match",
                        ["get", "text_anchor"],
                        ["left", "bottom-left", "top-left"],
                        "left",
                        ["right", "bottom-right", "top-right"],
                        "right",
                        "center"
                    ],
                    7,
                    "auto"
                ],
                "text-size": [
                    "interpolate",
                    ["cubic-bezier", 0.2, 0, 0.7, 1],
                    ["zoom"],
                    1,
                    ["step", ["get", "symbolrank"], 11, 4, 9, 5, 8],
                    9,
                    ["step", ["get", "symbolrank"], 22, 4, 19, 5, 17]
                ]
            },
            "paint": {
                "icon-opacity": [
                    "step",
                    ["zoom"],
                    ["case", ["has", "text_anchor"], 1, 0],
                    7,
                    0
                ],
                "text-color": "hsl(0, 0%, 95%)",
                "text-halo-color": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    2,
                    "hsla(0, 5%, 0%, 0.75)",
                    3,
                    "hsl(0, 5%, 0%)"
                ],
                "text-halo-width": 1.25
            }
        },
        {
            "id": "continent-label",
            "type": "symbol",
            "metadata": {
                "mapbox:featureComponent": "place-labels",
                "mapbox:group": "Place labels, place-labels"
            },
            "source": "composite",
            "source-layer": "natural_label",
            "minzoom": 0.75,
            "maxzoom": 3,
            "filter": ["==", ["get", "class"], "continent"],
            "layout": {
                "text-field": ["coalesce", ["get", "name_en"], ["get", "name"]],
                "text-line-height": 1.1,
                "text-max-width": 6,
                "text-font": ["DIN Pro Medium", "Arial Unicode MS Regular"],
                "text-size": [
                    "interpolate",
                    ["exponential", 0.5],
                    ["zoom"],
                    0,
                    10,
                    2.5,
                    15
                ],
                "text-transform": "uppercase",
                "text-letter-spacing": 0.05
            },
            "paint": {
                "text-color": "hsl(0, 0%, 95%)",
                "text-halo-color": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    0,
                    "hsla(0, 5%, 0%, 0.75)",
                    3,
                    "hsl(0, 5%, 0%)"
                ],
                "text-halo-width": 1.5,
                "text-opacity": [
                    "interpolate",
                    ["linear"],
                    ["zoom"],
                    0,
                    0.8,
                    1.5,
                    0.5,
                    2.5,
                    0
                ]
            }
        }
    ],
    "created": "2022-07-11T13:11:43.322Z",
    "modified": "2022-11-22T09:18:42.886Z",
    "id": "cl5grk342004114pvuo5mhar5",
    "owner": "davelf",
    "visibility": "private",
    "protected": false,
    "draft": false
}