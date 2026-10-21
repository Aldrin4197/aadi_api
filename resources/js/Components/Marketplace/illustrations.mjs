// Original vector artwork from the Aadi rental explorer prototype.
// These are category illustrations, never photos of a particular listing.
export function listingIllustration(service, type = '') {
    let name = 'space-studio';
    if (service === 'spaces' && type === 'commercial') name = 'space-cowork';
    if (service === 'haul') name = type === 'truck' ? 'vehicle-truck' : 'vehicle-van';
    if (service === 'raketeer') {
        name = ['itSupport', 'tutoring', 'tutoringService'].includes(type) ? 'raket-design' : 'raket-repair';
    }
    return `/images/illustrations/${name}.svg`;
}
