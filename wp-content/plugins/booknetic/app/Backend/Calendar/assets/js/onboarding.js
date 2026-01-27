const introJS = introJs();
let hasOnboardingStarted = false;

function startOnboarding(element) {
    let isOnboardingComplete = localStorage.getItem("booknetic_calendar_onboarding_done") === "1";

    if (hasOnboardingStarted || isOnboardingComplete) return;

    const eventDate = element.event.start;
    const viewStart = element.view.currentStart;

    const eventMonth = eventDate.getMonth();
    const eventYear = eventDate.getFullYear();
    const viewMonth = viewStart.getMonth();
    const viewYear = viewStart.getFullYear();

    const isInCurrentMonth = eventMonth === viewMonth && eventYear === viewYear;

    if (!isInCurrentMonth) return;

    hasOnboardingStarted = true;

    introJS.setOptions({
       steps: [
           {
               element: document.querySelector('.fc-day-grid-event'),
               title: booknetic.__("Quick actions on appointment card"),
               intro: booknetic.__("You can now right click to access quick actions"),
               position: "left",
           },
       ],
       showSkip: false,
       showStepNumbers: false,
       exitOnOverlayClick: false,
       showBullets: false,
       exitOnEsc: true,
       tooltipClass: "custom-tooltip",
    });

    introJS.start();

    introJS.oncomplete(function() {
        localStorage.setItem("booknetic_calendar_onboarding_done", "1");
    });

    introJS.onexit(function () {
        localStorage.setItem("booknetic_calendar_onboarding_done", "1");
    });
}
