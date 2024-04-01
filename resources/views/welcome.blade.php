<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Find Nearest Location</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/map.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/@geoapify/leaflet-address-search-plugin@^1/dist/L.Control.GeoapifyAddressSearch.min.css" />
</head>

<body>
        <div class="p-4 w-20  cstm-popup " style="display: none;">
            <h4 class="cstm-heading-modal position-relative">Find Nearest Postbox</h4>
            <svg class="position-absolute top-0 cstm-svg2" height="60" width="190">
                <g fill="none" stroke="#80FF8C">
                    <path stroke-linecap="round" stroke-width="6" d="M5 40 l215 0" />
                </g>
            </svg>
            <div>               
                <p class="p-0 m-0 mb-2">Enter your address to see the postboxes around. </p>
            </div>
            <div id="search-here-sidebar" class="mb-4 mt-2"></div>
            <img class="w-100" src="{{ asset('/images/posteImg.png') }}" alt="post img">
        </div>
        <div onclick="getUserLocation()" class="cstm-current-location">
            <img  src="{{ asset('/images/currentLocationGreen.png') }}" alt="">
        </div>

        <!-- map -->
        <div class="position-absolute cstm-map" id="map" style="width: 100%; height: 100%;"></div>
    </div>
    <!-- modal to ASK location on -->
    <!-- <div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body d-flex align-items-center ">
                    <div class="w-100 cstm-modal-div">
                        <div class="text-center">                          
                            <p> <b>To provide you with accurate location-based services, we need access to your device's location. This allows us to show your current location on the map.</b> </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/@geoapify/leaflet-address-search-plugin@^1/dist/L.Control.GeoapifyAddressSearch.min.js">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.7/axios.min.js"></script>
    <script>
 
        const x = document.getElementById("demo");
        let map;
        let currentUserMarker;
        let additionalMarkers = [];


        function debounce(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }
     
        $(document).on("DOMContentLoaded", function() {
            // console.log($('.leaflet-popup').find('.cstm-heading-modal'));
            // console.log('domm');
            // if ($('.leaflet-popup').find('.cstm-heading-modal')) {
            //     $('.leaflet-popup').addClass('cstm-popup');
            // }
            $('.cstm-popup').show();
            const italyLatLng = [41.8719, 12.5674];
            let apiKey = "9098219d18994560be55415be86df062";
            const searchContainerSideBar = document.getElementById('search-here-sidebar');
            const searchContainerPopup = document.getElementById('search-here-popup');
            
           
            const addressSearchControl = L.control.addressSearch(apiKey, {
                position: 'topleft',
                className: 'mb-2 position-relative cstm-search w-100',
                mapViewBias: true,
                placeholder: "Enter your address",

                resultCallback: (address) => {
                    if (currentUserMarker) {
                        //  $('.pop-heading').parents().filter('.leaflet-popup').addClass('cstm-marker-popup');

                        // if ($('.leaflet-popup').find('.cstm-heading-modal')) {
                        //    $('.leaflet-popup').addClass('cstm-popup');
                        // }
                        currentUserMarker.remove();
                    }
                    if (!address) {
                        return;
                    }

                    const customIcon = L.icon({
                        iconUrl: '/images/defaultMarker.png',
                        iconSize: [38, 38],
                        iconAnchor: [19, 38],
                        popupAnchor: [0, -38],
                        className: 'default-marker-icon',
                        html: '<div style="margin-left: 0 !important;"></div>'
                    });

                    currentUserMarker = L.marker([address.lat, address.lon], {
                        icon: customIcon
                    }).addTo(map);

                    currentUserMarker.bindPopup('<b>' + address.address_line1 + '</b>').openPopup();
                    map.setView([address.lat, address.lon], 14);
                    closeModalAndShowLocation();
                    
                    // var iconContainer = document.querySelector('.icon-container');
                    // iconContainer.classList.remove('open');
                    // iconContainer.classList.toggle('closed');
                },
            });

            map = L.map('map').setView(italyLatLng, 8); // Italy's coordinates

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            var center = null;

            getUserLocation();

            var updateMarkersDebounced = debounce(updateMarkers, 1000);

            map.on('moveend', function(e) {
                var updatedCenter = map.getCenter();
                if (!center ||
                    Math.abs(center.lat - updatedCenter.lat) >= 0.01 ||
                    Math.abs(center.lng - updatedCenter.lng) >= 0.01) {
                    center = updatedCenter;
                   updateMarkersDebounced();
                }
            });

            async function updateMarkers() {
                const customIcon = L.icon({
                    iconUrl: '/images/postRedIcon.png',
                    iconSize: [38, 38],
                    iconAnchor: [19, 38],
                    popupAnchor: [0, -38],
                    className: 'default-post-icon',

                });
                nearestLocations = [

                    // {
                    //     lat = '31.4828768',
                    //     lng = '74.2596828',
                    //     postCode = '54600',
                    //     areaName = 'Model Town, Lahore, Punjab AIzaSyA_L-LDpdgp2QRNyRsWM8dfDta8lgfEqvU',

                    // },
                    // {
                    //     lat = '31.4685025',
                    //     lng = '74.2653194',
                    //     postCode = '54600',
                    //     areaName = 'Johar Town, Lahore, Punjab',
                    // },
                    // {
                    //     lat = '31.4685025',
                    //     lng = '74.2653194',
                    //     postCode = '54600',
                    //     areaName = 'DHA, Lahore, Punjab',

                    // },

                ];

                    // $.ajax({
                    // url: '/nearest-locations',
                    // type: 'POST',
                    // dataType: 'json',
                    // data: {
                    //     latitude: center.lat,
                    //     longitude: center.lng
                    // },
                    // success: function(response) {
                        
                        additionalMarkers = [];

                        // response.
                        nearestLocations.forEach(location => {
                            var markerExists = additionalMarkers.some(marker => {
                                return marker.getLatLng().equals([location.lat, location.lng]);
                            });

                            if (!markerExists) {
                                
                                const additionalMarker = L.marker([location.lat, location.lng], 
                                {
                                    icon: customIcon
                                }).addTo(map);

                                additionalMarkers.push(additionalMarker);
                                const address = toPascalCase(location.areaName)+' '+location.postCode;
                                const areaName =  truncateAddress(address, 30);
                                const distance = calculateDistance(center.lat, center.lng, location.lat, location.lng);
                                const time = calculateTime(distance);

                                const googleMapsUrl = 
                                
                                
                                `https://www.google.com/maps/search/?api=1&query=${location.lat},${location.lng}`;

                                // href="https://maps.google.com/maps?saddr=current+location&daddr=(31.4747970, 74.4027630)"
                                // const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${location.lat},${location.lng}`;

                                const popupContent = `<div>
                                                        <h6 class="cstm-heading-modal pop-heading">PostBox Location</h6>
                                                        <div class="d-flex justify-content-between align-items-center area-time">
                                                            <h5 style="text-transform:capitalize;">${areaName}</h5>
                                                            <p class="m-0 p-0 cstm-time">${distance.toFixed(2)} km / ${time} mins</->
                                                        </div>
                                                        <div>
                                                        <img class="w-100" src="{{ asset('/images/rectanglePosteImg.png') }}"  alt="rectangle Post Img">
                                                        
                                                        </div>
                                                        <div class="popup-text">
                                                        <p>Click the button to get directions using Google Maps. </p>
                                                        </div>
                                                        <div class="text-center">
                                                            <a href="${googleMapsUrl}" target="_blank"> <button class="cstm-button">Get Direction</button></a>
                                                        </div>
                                                    </div>`;

                                additionalMarker.bindPopup(popupContent);
                                map.on('popupopen', function(e) {
                                    const popHeadings = $(e.popup._contentNode).find('.pop-heading').parentsUntil('.leaflet-popup-pane').addClass('cstm-marker-popup');
                                });

                            } else {
                                console.log('Marker already added!');
                            }
                        });
                    // },
                    // error: function(error) {
                    //     console.error('Error fetching nearest locations:', error);
                    // }
                }
                // );}

            function toPascalCase(str) {
                return str.toLowerCase().replace(/(?:^|\s)\w/g, function(match) {
                    return match.toUpperCase();
                }).replace(/\s+/g, ' ');    
            }

            function truncateAddress(address, maxLength) {
                if (address.length <= maxLength) {                    
                    return address;
                } else {
                    return address.slice(0, maxLength - 3) + '...';
                }
            }

            const customIcon = L.icon({
                iconUrl: '/images/defaultMarker.png',
                iconSize: [38, 38],
                iconAnchor: [19, 38],
                popupAnchor: [0, -38],
                className: 'default-marker-icon',
                html: '<div style="margin-left: 0 !important;"></div>'
            });

            // Initialize current user marker
            currentUserMarker = L.marker([0, 0], {
                icon: customIcon
            }).addTo(map);

            map.addControl(addressSearchControl);
            // searchContainerPopup.appendChild(addressSearchControl.getContainer());
            function appendAddressSearchControl() {
                    searchContainerSideBar.appendChild(addressSearchControl.getContainer());
                    closeModalAndShowLocation();
            }
                appendAddressSearchControl();
        });

        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {                
                console.log("Geolocation is not supported by this browser.");
            }
        };

        function closeModalAndShowLocation() {
            $('.cstm-popup').show();
        };

        function showPosition(position) {

            // Convert EPSG:3857 to EPSG:4326
            const userLocation = [position.coords.latitude, position.coords.longitude];
            // Update map to show user's location
            map.setView(userLocation, 14);
            currentUserMarker.setLatLng(userLocation);
            // Add custom marker for user's current location
            additionalMarkers = [];
            const customIcon = L.icon({
                iconUrl: '/images/red.png',
                iconSize: [38, 38],
                iconAnchor: [19, 38],
                popupAnchor: [0, -38],
                className: 'default-post-icon',
            });

            // Open popup for current user marker
            const currentUserPopupContent = '<b>Your Location</b>';
            currentUserMarker.bindPopup(currentUserPopupContent).openPopup();
        }

        // Function to calculate distance between two coordinates using Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // Radius of the Earth in kilometers
            const dLat = deg2rad(lat2 - lat1);
            const dLon = deg2rad(lon2 - lon1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            const distance = R * c; // Distance in kilometers
            return distance;
        }

        // Function to convert degrees to radians
        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        // Function to calculate time from distance (assumed average speed of 50 km/h)
        function calculateTime(distance) {
            const averageSpeed = 50; // Average speed in km/h
            const time = (distance / averageSpeed) * 60 * 4; // Time in minutes
            return Math.round(time); // Round to nearest whole minute
        }


        function showError(error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                             
                    console.log("User denied the request for Geolocation.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    console.log("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    console.log("The request to get user location timed out.");
                    break;
                case error.UNKNOWN_ERROR:
                    console.log("An unknown error occurred.");
                    break;
            }
        }

        function toggleMenu() {
            var iconContainer = document.querySelector('.icon-container');
            iconContainer.classList.toggle('open');            
            // Check if the icon container has the "open" class
            if (iconContainer.classList.contains('open')) {
                $('.cstm-popup').show(); // Show the popup
            } else {
                // Check if the display width is 768 pixels or less
                if (window.innerWidth <= 768) {
                    $('.cstm-popup').hide(); // Hide the popup
                }
            }
        }
    </script>

</body>

</html>
