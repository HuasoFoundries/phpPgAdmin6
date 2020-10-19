import * as jQuery from 'jquery';

declare global {
    interface Window {
        jQuery: typeof jQuery;
        $: typeof jQuery;
    }
}