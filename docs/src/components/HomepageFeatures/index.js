import React from 'react';
import clsx from 'clsx';
import styles from './styles.module.css';

const FeatureList = [
  {
    title: '39 Token Sets',
    description: (
      <>
        Pre-configured themes for Dutch municipalities, provinces, and national government. From Rijkshuisstijl to Amsterdam, Utrecht, Den Haag, and 35 more.
      </>
    ),
  },
  {
    title: '7-Layer CSS Architecture',
    description: (
      <>
        Layered CSS system that translates NL Design System tokens into Nextcloud styling. Incomplete token sets gracefully fall back to defaults.
      </>
    ),
  },
  {
    title: 'Government Compliant',
    description: (
      <>
        WCAG AA accessible and compliant with Dutch government design standards including Rijkshuisstijl guidelines.
      </>
    ),
  },
  {
    title: 'One-Click Configuration',
    description: (
      <>
        Select your organization from a dropdown in admin settings. Primary color, background, and logo sync automatically.
      </>
    ),
  },
  {
    title: 'App Compatible',
    description: (
      <>
        Works with all Nextcloud apps that use standard CSS variables. No changes needed in other apps for theming to apply.
      </>
    ),
  },
  {
    title: 'Open Source',
    description: (
      <>
        Built on the NL Design System community. Token sets sourced from official government design repositories. AGPL-3.0 licensed.
      </>
    ),
  },
];

function Feature({title, description}) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center padding-horiz--md">
        <h3>{title}</h3>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}
