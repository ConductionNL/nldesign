import React from 'react';
import clsx from 'clsx';
import styles from './styles.module.css';

const FeatureList = [
  {
    title: '40 Profile Snapshots',
    description: (
      <>
        Eight statically gated projections are selectable; the other 32 records remain source-only.
      </>
    ),
  },
  {
    title: '3-Layer Bounded Cascade',
    description: (
      <>
        Local fonts, one complete selected projection, and a small semantic mapping—with no cross-profile fallback.
      </>
    ),
  },
  {
    title: 'Evidence First',
    description: (
      <>
        Provenance, identity rights, accessibility, and Nextcloud compatibility are tracked as separate evidence—not inferred from a profile name.
      </>
    ),
  },
  {
    title: 'Revision-Checked Changes',
    description: (
      <>
        Profile updates reject stale administrator writes and retain rollback context plus bounded history.
      </>
    ),
  },
  {
    title: 'Scoped Runtime',
    description: (
      <>
        Public Nextcloud APIs power the profile path. Private Theming integration remains isolated, optional, and currently unregistered.
      </>
    ),
  },
  {
    title: 'Open Source',
    description: (
      <>
        App code is EUPL-1.2 and Fira Sans is self-hosted under SIL OFL 1.1. Identity rights remain separate.
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
