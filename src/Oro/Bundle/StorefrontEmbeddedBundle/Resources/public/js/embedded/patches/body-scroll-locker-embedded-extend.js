import bodyScrollLocker from 'oroui/js/app/services/body-scroll-locker';
import {lockHostScrollFor, unlockHostScrollFor} from '../host-scroll';

export function installBodyScrollLockerEmbeddedExtend() {
    bodyScrollLocker.addLocker = lockHostScrollFor;
    bodyScrollLocker.removeLocker = unlockHostScrollFor;
}
