import './bootstrap';
import { startFilter } from './filter';
import { startHero } from './hero';
import { startLightbox } from './lightbox';
import { startMenu } from './menu';
import { startMotion } from './motion';
import { startNewsView } from './news-view';
import { startPopup } from './popup';
import { startMore, startRail } from './rail';
import { startSeen } from './seen';
import { startPostShare, startShare } from './share';
import { startSlider } from './slider';
import { startSpot } from './spot';
import { startSwipe } from './swipe';
import { startTheme } from './theme';
import { startFeedMore, startToc } from './toc';
import { startTop } from './top';

startTheme();
startPopup();
startMenu();
startSlider();
startSwipe();
startSpot();
startShare();
startPostShare();
startMotion();
startFilter();
startTop();
startToc();
startFeedMore();
startLightbox();
startHero();
startNewsView();
startRail();
startMore();
startSeen();

// The small program that answers with the phone numbers when there is no connection (SeoController::worker).
// Where a browser has none of that, or the page is not served securely, the site is simply as it was.
const worker = document.body.dataset.worker;

if (worker && 'serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => navigator.serviceWorker.register(worker).catch(() => {}));
}
